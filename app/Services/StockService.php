<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\StockException;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The only place that changes products.stock.
 *
 * Every change is a transaction with items. Approving it locks the affected products,
 * records stock_before/stock_after per item and updates the stock, all inside one
 * database transaction, so two people working at the same time can never lose or
 * double-count stock.
 */
class StockService
{
    /** Retries if MySQL still aborts a transaction as a deadlock victim. Only the outermost call retries. */
    private const ATTEMPTS = 3;

    /**
     * Goods received from a supplier. Counted as soon as they are recorded (approved by the recorder).
     *
     * @param  array{supplier_id: int, transaction_date: string, note?: string|null}  $header
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    public function recordGoodsIn(User $user, array $header, array $items): Transaction
    {
        return DB::transaction(function () use ($user, $header, $items) {
            // Lock the products before inserting items. The item insert's foreign key check takes a
            // shared lock on each product; locking afterwards would let two goods-in deadlock each other.
            $this->lockProducts(array_column($items, 'product_id'));
            $transaction = $this->create($user, TransactionType::In, $header, $items);
            $this->approve($transaction, $user);

            return $transaction;
        }, self::ATTEMPTS);
    }

    /**
     * A request to take goods out for a department. Stays pending until an admin decides; stock is untouched.
     *
     * @param  array{department_id: int, recipient_name: string, transaction_date: string, note?: string|null}  $header
     * @param  list<array{product_id: int, quantity: int}>  $items
     *
     * @throws StockException when a product does not have enough stock right now.
     */
    public function requestGoodsOut(User $user, array $header, array $items): Transaction
    {
        return DB::transaction(function () use ($user, $header, $items) {
            // Early check so nobody files a request that can never be approved. Approval checks again.
            $products = $this->lockProducts(array_column($items, 'product_id'));
            foreach ($items as $item) {
                $product = $products->get($item['product_id'])
                    ?? throw new StockException('Salah satu produk sudah dihapus.');
                if ($item['quantity'] > $product->stock) {
                    throw new StockException("Stok {$product->name} tidak cukup: tersedia {$product->stock} {$product->unit}, diminta {$item['quantity']}.");
                }
            }

            return $this->create($user, TransactionType::Out, $header, $items);
        }, self::ATTEMPTS);
    }

    /**
     * Admin approves a goods-out request: stock goes down.
     *
     * @throws StockException when the decision is not allowed or stock is no longer enough.
     */
    public function approveRequest(Transaction $transaction, User $admin): void
    {
        $this->ensureCanDecide($transaction, $admin);
        $this->approve($transaction, $admin);
    }

    /**
     * Admin rejects a goods-out request with a reason: stock is untouched.
     *
     * @throws StockException when the decision is not allowed.
     */
    public function rejectRequest(Transaction $transaction, User $admin, string $reason): void
    {
        $this->ensureCanDecide($transaction, $admin);

        DB::transaction(function () use ($transaction, $admin, $reason) {
            $locked = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if (! $locked->isPending()) {
                throw new StockException("Transaksi {$locked->code} sudah {$locked->status->label()}.");
            }

            $locked->status = TransactionStatus::Rejected;
            $locked->approved_by = $admin->id;
            $locked->approved_at = now();
            $locked->reject_reason = $reason;
            $locked->save();

            $transaction->setRawAttributes($locked->getAttributes(), true);
        }, self::ATTEMPTS);
    }

    /**
     * Applies a pending transaction to the stock and marks it approved.
     * Private on purpose: callers go through recordGoodsIn() or approveRequest(), which carry the rules.
     *
     * @throws StockException when the transaction is no longer pending or stock would go below zero.
     */
    private function approve(Transaction $transaction, User $approver): void
    {
        DB::transaction(function () use ($transaction, $approver) {
            // Lock the transaction row first so it cannot be approved twice at the same time.
            $locked = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if (! $locked->isPending()) {
                throw new StockException("Transaksi {$locked->code} sudah {$locked->status->label()}.");
            }

            $items = $locked->items()->get();
            $products = $this->lockProducts($items->pluck('product_id')->all());

            foreach ($items as $item) {
                $product = $products->get($item->product_id)
                    ?? throw new StockException('Salah satu produk sudah dihapus. Transaksi tidak dapat diproses.');

                $after = $product->stock + $this->direction($locked->type) * $item->quantity;
                if ($after < 0) {
                    throw new StockException("Stok {$product->name} tidak cukup: tersedia {$product->stock} {$product->unit}, diminta {$item->quantity}.");
                }

                $item->stock_before = $product->stock;
                $item->stock_after = $after;
                $item->save();

                $product->stock = $after;
                $product->save();
            }

            $locked->status = TransactionStatus::Approved;
            $locked->approved_by = $approver->id;
            $locked->approved_at = now();
            $locked->save();

            $transaction->setRawAttributes($locked->getAttributes(), true);
        }, self::ATTEMPTS);
    }

    /**
     * Locks products in id order. Every writer takes locks in the same order, so they never deadlock.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Product>
     */
    private function lockProducts(array $ids): Collection
    {
        return Product::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    private function create(User $user, TransactionType $type, array $header, array $items): Transaction
    {
        $transaction = new Transaction($header);
        $transaction->type = $type;
        $transaction->status = TransactionStatus::Pending;
        $transaction->created_by = $user->id;
        // Temporary unique code; the real one uses the id, which is unique and needs no counter table.
        $transaction->code = (string) Str::ulid();
        $transaction->save();

        $transaction->code = sprintf('%s-%s-%05d', $type->codePrefix(), $transaction->transaction_date->format('Ymd'), $transaction->id);
        $transaction->save();

        foreach ($items as $item) {
            $transaction->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return $transaction;
    }

    /**
     * Separation of duties: only an admin decides, and never on a request they made themselves.
     * Checked here, not only in the controller, so no future caller can skip it.
     */
    private function ensureCanDecide(Transaction $transaction, User $admin): void
    {
        if ($transaction->type !== TransactionType::Out) {
            throw new StockException('Hanya permintaan barang keluar yang dapat disetujui atau ditolak.');
        }
        if (! $admin->isAdmin()) {
            throw new StockException('Hanya admin yang dapat memutuskan permintaan.');
        }
        if ((int) $transaction->created_by === $admin->id) {
            throw new StockException('Anda tidak dapat memutuskan permintaan yang Anda buat sendiri. Minta admin lain untuk memeriksanya.');
        }
    }

    private function direction(TransactionType $type): int
    {
        return match ($type) {
            TransactionType::In => 1,
            TransactionType::Out => -1,
            TransactionType::Adjustment => throw new StockException('Penyesuaian stok diproses terpisah.'),
        };
    }
}
