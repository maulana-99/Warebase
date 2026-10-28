<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\StockException;
use App\Http\Requests\Transactions\GoodsOutRequest;
use App\Models\Department;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Goods-out requests. Anyone can file one; an admin who did not file it approves or rejects it.
 * Staff only see their own requests; admins see all.
 */
class GoodsRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = $request->string('q')->trim()->toString();
        // Escape LIKE wildcards so "%" or "_" are searched literally.
        $pattern = '%'.addcslashes($search, '%_\\').'%';
        $status = TransactionStatus::tryFrom($request->string('status')->toString());

        $transactions = Transaction::query()
            ->where('type', TransactionType::Out)
            ->unless($user->isAdmin(), fn ($query) => $query->where('created_by', $user->id))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, fn ($query) => $query->where(fn ($q) => $q
                ->where('code', 'like', $pattern)
                ->orWhere('recipient_name', 'like', $pattern)
                ->orWhereHas('department', fn ($d) => $d->withTrashed()->where('name', 'like', $pattern))))
            ->with(['department', 'creator'])
            ->withCount('items')
            ->withSum('items', 'quantity')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('requests.index', compact('transactions', 'search', 'status'));
    }

    public function create(): View
    {
        return view('requests.create', [
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'sku', 'name', 'unit', 'stock']),
        ]);
    }

    public function store(GoodsOutRequest $request, StockService $stock): RedirectResponse
    {
        try {
            $transaction = $stock->requestGoodsOut(
                $request->user(),
                $request->safe()->only(['department_id', 'recipient_name', 'transaction_date', 'note']),
                $request->validated('items'),
            );
        } catch (StockException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('requests.show', $transaction)
            ->with('success', "Permintaan {$transaction->code} terkirim dan menunggu persetujuan admin.");
    }

    public function show(Request $request, Transaction $transaction): View
    {
        $this->authorizeView($request, $transaction);

        $transaction->load(['items.product', 'department', 'creator', 'approver']);
        $user = $request->user();

        return view('transactions.show', [
            'transaction' => $transaction,
            'backRoute' => route('requests.index'),
            'canDecide' => $user->isAdmin() && $transaction->isPending() && (int) $transaction->created_by !== $user->id,
        ]);
    }

    public function approve(Request $request, Transaction $transaction, StockService $stock): RedirectResponse
    {
        try {
            $stock->approveRequest($transaction, $request->user());
        } catch (StockException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Permintaan {$transaction->code} disetujui. Stok sudah dikurangi.");
    }

    public function reject(Request $request, Transaction $transaction, StockService $stock): RedirectResponse
    {
        $validated = $request->validate(
            ['reject_reason' => ['required', 'string', 'max:500']],
            attributes: ['reject_reason' => 'alasan penolakan'],
        );

        try {
            $stock->rejectRequest($transaction, $request->user(), $validated['reject_reason']);
        } catch (StockException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Permintaan {$transaction->code} ditolak.");
    }

    /**
     * Not a goods-out request, or a staff member looking at someone else's: answer 404 so ids leak nothing.
     */
    private function authorizeView(Request $request, Transaction $transaction): void
    {
        $user = $request->user();

        abort_unless(
            $transaction->type === TransactionType::Out && ($user->isAdmin() || (int) $transaction->created_by === $user->id),
            404,
        );
    }
}
