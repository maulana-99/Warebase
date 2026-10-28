<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only audit trail for admins.
 */
class ActivityLogController extends Controller
{
    /** Filter groups: action prefix => label. */
    public const GROUPS = [
        'auth' => 'Login',
        'user' => 'Pengguna',
        'master' => 'Data master',
        'product' => 'Produk',
        'goods_in' => 'Barang masuk',
        'request' => 'Permintaan',
        'adjustment' => 'Penyesuaian',
    ];

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        // Escape LIKE wildcards so "%" or "_" are searched literally.
        $pattern = '%'.addcslashes($search, '%_\\').'%';
        $group = array_key_exists($request->string('group')->toString(), self::GROUPS) ? $request->string('group')->toString() : null;
        $userId = $request->integer('user') ?: null;

        $logs = ActivityLog::query()
            ->with('user')
            ->when($group, fn ($q) => $q->where('action', 'like', $group.'.%'))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('description', 'like', $pattern)
                ->orWhere('ip_address', 'like', $pattern)))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'groups' => self::GROUPS,
            'search' => $search,
            'group' => $group,
            'userId' => $userId,
        ]);
    }
}
