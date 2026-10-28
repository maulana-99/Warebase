<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Exceptions\StockException;
use App\Http\Requests\Transactions\GoodsInRequest;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Goods received from suppliers. Admin and staff can record; stock goes up immediately.
 */
class GoodsInController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        // Escape LIKE wildcards so "%" or "_" are searched literally.
        $pattern = '%'.addcslashes($search, '%_\\').'%';

        $transactions = Transaction::query()
            ->where('type', TransactionType::In)
            ->with(['supplier', 'creator'])
            ->withCount('items')
            ->withSum('items', 'quantity')
            ->when($search, fn ($query) => $query->where(fn ($q) => $q
                ->where('code', 'like', $pattern)
                ->orWhereHas('supplier', fn ($s) => $s->withTrashed()->where('name', 'like', $pattern))))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('goods-in.index', compact('transactions', 'search'));
    }

    public function create(): View
    {
        return view('goods-in.create', [
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'sku', 'name', 'unit', 'stock']),
        ]);
    }

    public function store(GoodsInRequest $request, StockService $stock): RedirectResponse
    {
        try {
            $transaction = $stock->recordGoodsIn(
                $request->user(),
                $request->safe()->only(['supplier_id', 'transaction_date', 'note']),
                $request->validated('items'),
            );
        } catch (StockException $e) {
            // A validation error (422 in the modal) keeps everything the user typed.
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }

        $this->audit('goods_in.recorded', "Mencatat barang masuk {$transaction->code}", $transaction);

        return redirect()->route('goods-in.show', $transaction)
            ->with('success', "Barang masuk {$transaction->code} berhasil dicatat. Stok sudah bertambah.");
    }

    public function show(Transaction $transaction): View
    {
        abort_unless($transaction->type === TransactionType::In, 404);

        $transaction->load(['items.product', 'supplier', 'creator', 'approver']);

        return view('transactions.show', [
            'transaction' => $transaction,
            'backRoute' => route('goods-in.index'),
        ]);
    }
}
