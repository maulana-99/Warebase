<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Product;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reports for a period: stock movement per product, and the work trail per user.
 * Movements count when stock actually changed, i.e. by approval time.
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to, $categoryId] = $this->filters($request);
        $tab = $request->string('tab')->toString() === 'staff' ? 'staff' : 'stock';

        return view('reports.index', [
            'tab' => $tab,
            'from' => $from,
            'to' => $to,
            'categoryId' => $categoryId,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'products' => $tab === 'stock' ? $this->stockMovements($from, $to, $categoryId) : collect(),
            'users' => $tab === 'staff' ? $this->workTrail($request->user(), $from, $to) : collect(),
        ]);
    }

    public function stockCsv(Request $request): StreamedResponse
    {
        [$from, $to, $categoryId] = $this->filters($request);
        $products = $this->stockMovements($from, $to, $categoryId);

        return response()->streamDownload(function () use ($products) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SKU', 'Produk', 'Kategori', 'Satuan', 'Stok awal', 'Masuk', 'Keluar', 'Penyesuaian', 'Stok akhir']);
            foreach ($products as $product) {
                fputcsv($out, array_map($this->csvSafe(...), [
                    $product->sku, $product->name, $product->category->name, $product->unit,
                    $product->opening, $product->qty_in, $product->qty_out, $product->qty_adjustment, $product->closing,
                ]));
            }
            fclose($out);
        }, "laporan-stok-{$from->toDateString()}-{$to->toDateString()}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{Carbon, Carbon, int|null}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'category' => ['nullable', 'integer'],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now())->endOfDay();
        // Covers the defaults too (e.g. only a future "from"), which the rules above cannot see.
        if ($to->lt($from)) {
            throw ValidationException::withMessages(['to' => 'Tanggal akhir tidak boleh sebelum tanggal awal.']);
        }

        return [$from, $to, isset($validated['category']) ? (int) $validated['category'] : null];
    }

    /**
     * Per product: balance before the period, in/out/adjustment inside it, balance at its end.
     * opening + in - out + adjustment = closing always holds, because every change is an approved item.
     */
    private function stockMovements(Carbon $from, Carbon $to, ?int $categoryId)
    {
        $approved = fn () => TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereColumn('transaction_items.product_id', 'products.id')
            ->where('transactions.status', TransactionStatus::Approved);

        $balanceAt = fn (string $operator, Carbon $moment) => $approved()
            ->select('transaction_items.stock_after')
            ->where('transactions.approved_at', $operator, $moment)
            ->orderByDesc('transactions.approved_at')
            ->orderByDesc('transactions.id')
            ->limit(1);

        $inPeriod = fn (TransactionType $type, string $expression) => $approved()
            ->selectRaw("COALESCE(SUM({$expression}), 0)")
            ->where('transactions.type', $type)
            ->whereBetween('transactions.approved_at', [$from, $to]);

        return Product::query()
            ->with('category')
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->select('products.*')
            ->selectSub($balanceAt('<', $from), 'opening')
            ->selectSub($balanceAt('<=', $to), 'closing')
            ->selectSub($inPeriod(TransactionType::In, 'transaction_items.quantity'), 'qty_in')
            ->selectSub($inPeriod(TransactionType::Out, 'transaction_items.quantity'), 'qty_out')
            // Adjustments store an unsigned difference; the signed change is after - before.
            ->selectSub($inPeriod(TransactionType::Adjustment, 'CAST(transaction_items.stock_after AS SIGNED) - CAST(transaction_items.stock_before AS SIGNED)'), 'qty_adjustment')
            ->orderBy('name')
            ->get()
            ->each(function (Product $product) {
                foreach (['opening', 'closing', 'qty_in', 'qty_out', 'qty_adjustment'] as $column) {
                    $product->{$column} = (int) $product->{$column};
                }
            });
    }

    /**
     * Who did how much in the period. Staff only see their own row.
     */
    private function workTrail(User $viewer, Carbon $from, Carbon $to)
    {
        $created = fn (TransactionType $type) => fn ($q) => $q->where('type', $type)->whereBetween('created_at', [$from, $to]);
        $decided = fn (TransactionStatus $status) => fn ($q) => $q->where('type', TransactionType::Out)->where('status', $status)->whereBetween('approved_at', [$from, $to]);

        return User::query()
            ->unless($viewer->isAdmin(), fn ($q) => $q->whereKey($viewer->id))
            ->withCount([
                'createdTransactions as goods_in_count' => $created(TransactionType::In),
                'createdTransactions as requests_count' => $created(TransactionType::Out),
                'createdTransactions as adjustments_count' => $created(TransactionType::Adjustment),
                'approvedTransactions as approved_count' => $decided(TransactionStatus::Approved),
                'approvedTransactions as rejected_count' => $decided(TransactionStatus::Rejected),
                'activityLogs as activity_count' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
            ])
            ->withMax(['activityLogs as last_activity' => fn ($q) => $q->whereBetween('created_at', [$from, $to])], 'created_at')
            ->orderBy('name')
            ->get();
    }

    /**
     * Spreadsheet apps run cells starting with = + - @ as formulas; prefix them so names stay plain text.
     */
    private function csvSafe(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
