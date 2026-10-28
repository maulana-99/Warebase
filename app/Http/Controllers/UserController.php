<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Users\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * User management (admin only). This is the only way to register new accounts.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        // Escape LIKE wildcards so "%" or "_" are searched literally.
        $pattern = '%'.addcslashes($search, '%_\\').'%';

        $users = User::query()
            ->when($search, fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', $pattern)
                ->orWhere('email', 'like', $pattern)))
            ->withCount('createdTransactions')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        return view('users.create', ['user' => new User(['role' => Role::Staff])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->only(['name', 'email', 'password']));
        $user->role = $request->enum('role', Role::class);
        $user->save();

        return redirect()->route('users.index')->with('success', "Akun {$user->name} berhasil dibuat.");
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $role = $request->enum('role', Role::class);

        if ($user->is($request->user()) && $role !== $user->role) {
            return back()->withInput()->with('error', 'Anda tidak dapat mengubah peran akun Anda sendiri.');
        }

        $user->fill($request->safe()->only(['name', 'email']));
        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }
        $user->role = $role;
        $user->save();

        return redirect()->route('users.index')->with('success', "Akun {$user->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Users with any history stay, so transactions and the activity log keep their name.
        if ($user->createdTransactions()->exists() || $user->approvedTransactions()->exists() || $user->activityLogs()->exists()) {
            return back()->with('error', "Akun {$user->name} tidak dapat dihapus karena memiliki riwayat transaksi atau aktivitas.");
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', "Akun {$user->name} berhasil dihapus.");
    }
}
