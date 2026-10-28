<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $thisMonth = Transaction::where('status', TransactionStatus::Approved)
            ->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $lastMonth = Transaction::where('status', TransactionStatus::Approved)
            ->whereBetween('transaction_date', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])
            ->count();

        return view('dashboard', [
            'stats' => [
                'products' => Product::count(),
                'lowStock' => Product::lowStock()->count(),
                'pendingRequests' => Transaction::where('type', TransactionType::Out)->where('status', TransactionStatus::Pending)->count(),
                'transactionsThisMonth' => $thisMonth,
                'transactionsChange' => $lastMonth > 0 ? round(($thisMonth - $lastMonth) / $lastMonth * 100, 1) : null,
            ],
            'movement' => $this->movementLastSevenDays(),
            'categories' => $this->productsPerCategory(),
            'lowStockProducts' => Product::lowStock()->with('location')->orderBy('stock')->limit(5)->get(),
            'recentTransactions' => Transaction::with(['creator', 'supplier', 'department'])
                ->withSum('items', 'quantity')
                ->withCount('items')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * Approved quantity in and out per day for the last 7 days.
     *
     * @return array<int, array{date: Carbon, in: int, out: int}>
     */
    private function movementLastSevenDays(): array
    {
        $start = now()->subDays(6)->startOfDay();

        $rows = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', TransactionStatus::Approved->value)
            ->whereIn('transactions.type', [TransactionType::In->value, TransactionType::Out->value])
            ->where('transactions.transaction_date', '>=', $start->toDateString())
            ->groupBy('transactions.transaction_date', 'transactions.type')
            ->selectRaw('transactions.transaction_date as day, transactions.type, SUM(transaction_items.quantity) as total')
            ->get();

        return collect(range(0, 6))->map(function (int $offset) use ($start, $rows) {
            $date = $start->copy()->addDays($offset);
            $day = $rows->where('day', $date->toDateString());

            return [
                'date' => $date,
                'in' => (int) $day->firstWhere('type', TransactionType::In->value)?->total,
                'out' => (int) $day->firstWhere('type', TransactionType::Out->value)?->total,
            ];
        })->all();
    }

    /**
     * Product count per category with a chart color, largest first.
     *
     * @return array<int, array{name: string, count: int, color: string}>
     */
    private function productsPerCategory(): array
    {
        $colors = ['var(--color-peach)', 'var(--color-sky)', 'var(--color-sun)', 'var(--color-rose)', 'var(--color-mint)'];

        return Category::withCount('products')
            ->having('products_count', '>', 0)
            ->orderByDesc('products_count')
            ->get()
            ->values()
            ->map(fn (Category $category, int $i) => [
                'name' => $category->name,
                'count' => $category->products_count,
                'color' => $colors[$i % count($colors)],
            ])
            ->all();
    }
}
