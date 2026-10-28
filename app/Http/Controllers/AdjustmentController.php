<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Exceptions\StockException;
use App\Http\Requests\Transactions\AdjustmentRequest;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Stock adjustments after a physical count (admin only, see routes/web.php).
 */
class AdjustmentController extends Controller
{
    public function index(): View
    {
        $transactions = Transaction::query()
            ->where('type', TransactionType::Adjustment)
            ->with('creator')
            ->withCount('items')
            ->latest('id')
            ->paginate(10);

        return view('adjustments.index', compact('transactions'));
    }

    public function create(): View
    {
        return view('adjustments.create', [
            'products' => Product::orderBy('name')->get(['id', 'sku', 'name', 'unit', 'stock']),
        ]);
    }

    public function store(AdjustmentRequest $request, StockService $stock): RedirectResponse
    {
        try {
            $transaction = $stock->adjustStock(
                $request->user(),
                $request->safe()->only(['note']),
                $request->validated('items'),
                Carbon::parse($request->validated('counted_since')),
            );
        } catch (StockException $e) {
            // A validation error (422 in the modal) keeps everything the admin typed.
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }

        $this->audit('adjustment.recorded', "Menyesuaikan stok {$transaction->code}", $transaction);

        return redirect()->route('adjustments.show', $transaction)
            ->with('success', "Penyesuaian {$transaction->code} tersimpan. Stok sudah disesuaikan.");
    }

    public function show(Transaction $transaction): View
    {
        abort_unless($transaction->type === TransactionType::Adjustment, 404);

        $transaction->load(['items.product', 'creator', 'approver']);

        return view('transactions.show', [
            'transaction' => $transaction,
            'backRoute' => route('adjustments.index'),
        ]);
    }
}
