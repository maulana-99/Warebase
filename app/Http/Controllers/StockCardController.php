<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\Product;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kartu Stok: every approved movement of one product with its running balance.
 * The last recorded balance must equal the product's stock; a mismatch means stock changed outside a transaction.
 */
class StockCardController extends Controller
{
    public function __invoke(Request $request, Product $product): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        $movements = fn () => TransactionItem::query()
            ->select('transaction_items.*')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transaction_items.product_id', $product->id)
            ->where('transactions.status', TransactionStatus::Approved);

        $items = $movements()
            ->when($from, fn ($q) => $q->whereDate('transactions.approved_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('transactions.approved_at', '<=', $to))
            // Stock changes in approval order, so the running balance follows approved_at (then id for ties).
            ->orderBy('transactions.approved_at')
            ->orderBy('transactions.id')
            ->with(['transaction.supplier', 'transaction.department', 'transaction.creator'])
            ->paginate(20)
            ->withQueryString();

        $lastBalance = $movements()->orderByDesc('transactions.approved_at')->orderByDesc('transactions.id')->value('transaction_items.stock_after') ?? 0;

        return view('products.stock-card', [
            'product' => $product->load(['category', 'location']),
            'items' => $items,
            'from' => $from,
            'to' => $to,
            'balanced' => $lastBalance === $product->stock,
            'lastBalance' => $lastBalance,
        ]);
    }
}
