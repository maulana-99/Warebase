<?php

namespace App\Http\Controllers;

use App\Http\Requests\Master\MasterDataRequest;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One controller for the four master-data tables (categories, racks, suppliers, departments).
 * They share the same list/create/edit/delete flow, so each table is described in MasterDataRequest::TYPES.
 * Everyone can list; only admins can change (see routes/web.php).
 */
class MasterDataController extends Controller
{
    /** @var array<string, class-string<Model>> */
    private const MODELS = [
        'categories' => Category::class,
        'locations' => Location::class,
        'suppliers' => Supplier::class,
        'departments' => Department::class,
    ];

    public function list(Request $request, string $type): View
    {
        $config = MasterDataRequest::TYPES[$type];
        $search = $request->string('q')->trim()->toString();
        // Escape LIKE wildcards so "%" or "_" are searched literally.
        $pattern = '%'.addcslashes($search, '%_\\').'%';

        $items = self::MODELS[$type]::query()
            ->when($search, fn ($query) => $query->where(function ($q) use ($config, $pattern) {
                foreach ($config['search'] as $column) {
                    $q->orWhere($column, 'like', $pattern);
                }
            }))
            ->withCount($this->usage($config))
            ->orderBy($config['order'])
            ->paginate(10)
            ->withQueryString();

        return view('master.index', compact('type', 'config', 'items', 'search'));
    }

    public function create(string $type): View
    {
        return view('master.form', [
            'type' => $type,
            'config' => MasterDataRequest::TYPES[$type],
            'item' => new (self::MODELS[$type]),
        ]);
    }

    public function store(MasterDataRequest $request, string $type): RedirectResponse
    {
        $item = self::MODELS[$type]::create($request->validated());
        $this->audit('master.created', "Menambah {$request->config()['singular']} {$item->name}", $item);

        return redirect()->route('master.list', $type)
            ->with('success', "{$request->config()['singular']} {$item->name} berhasil ditambahkan.");
    }

    public function edit(string $type, int $id): View
    {
        return view('master.form', [
            'type' => $type,
            'config' => MasterDataRequest::TYPES[$type],
            'item' => self::MODELS[$type]::findOrFail($id),
        ]);
    }

    public function update(MasterDataRequest $request, string $type, int $id): RedirectResponse
    {
        $item = self::MODELS[$type]::findOrFail($id);
        $before = $item->getAttributes();
        $item->update($request->validated());
        $this->audit('master.updated', "Mengubah {$request->config()['singular']} {$item->name}", $item, $this->changes($item, $before));

        return redirect()->route('master.list', $type)
            ->with('success', "{$request->config()['singular']} {$item->name} berhasil diperbarui.");
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $config = MasterDataRequest::TYPES[$type];
        $item = self::MODELS[$type]::findOrFail($id);

        if ($item->{$config['used_by']}()->when($config['used_by'] === 'products', fn ($q) => $q->withTrashed())->exists()) {
            return back()->with('error', "{$config['singular']} {$item->name} masih dipakai oleh {$config['used_label']}, jadi tidak dapat dihapus.");
        }

        // Nothing points at it, so remove it for real. A soft delete would keep the unique name reserved.
        $item->forceDelete();
        $this->audit('master.deleted', "Menghapus {$config['singular']} {$item->name}");

        return redirect()->route('master.list', $type)
            ->with('success', "{$config['singular']} {$item->name} berhasil dihapus.");
    }

    /**
     * Relation count for the "Dipakai" column. Products are soft-deleted, and a trashed product
     * still holds its foreign key, so it counts as "in use". Transactions are never deleted.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, \Closure>
     */
    private function usage(array $config): array
    {
        return [$config['used_by'] => fn ($q) => $config['used_by'] === 'products' ? $q->withTrashed() : $q];
    }
}
