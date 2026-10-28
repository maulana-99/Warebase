<x-layouts.app heading="Produk">
    <form method="GET" role="search" class="mb-5 flex flex-wrap items-center gap-3">
        <div class="relative w-full sm:w-72">
            <label for="product-search" class="sr-only">Cari produk</label>
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-subtle" />
            <input id="product-search" name="q" type="search" value="{{ $search }}" placeholder="Cari SKU atau nama…" class="input pl-11">
        </div>
        <div>
            <label for="product-category" class="sr-only">Kategori</label>
            <select id="product-category" name="category" class="input" onchange="this.form.requestSubmit()">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($category->id === $categoryId)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex cursor-pointer items-center gap-2 text-sm">
            <input type="checkbox" name="low" value="1" class="accent-sky" @checked($lowOnly) onchange="this.form.requestSubmit()">
            Stok menipis saja
        </label>
        <button type="submit" class="btn-secondary">Terapkan</button>
        <p class="text-sm"><span class="font-semibold">{{ $products->total() }}</span> <span class="text-muted">produk</span></p>
        @if (auth()->user()->isAdmin())
            <a data-form-modal href="{{ route('products.create') }}" class="btn-primary ml-auto"><x-icon name="plus" class="size-4" /> Tambah produk</a>
        @endif
    </form>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="table-stack w-full min-w-[48rem] text-left text-sm">
                <caption class="sr-only">Daftar produk</caption>
                <thead class="bg-raised/60 text-xs tracking-wide text-muted uppercase">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">Produk</th>
                        <th scope="col" class="px-5 py-3 font-medium">Kategori</th>
                        <th scope="col" class="px-5 py-3 font-medium">Rak</th>
                        <th scope="col" class="px-5 py-3 font-medium">Stok</th>
                        <th scope="col" class="px-5 py-3 font-medium">Minimum</th>
                        @if (auth()->user()->isAdmin())
                            <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($products as $product)
                        <tr class="transition hover:bg-raised/40">
                            <td class="px-5 py-4">
                                <a href="{{ route('products.stock-card', $product) }}" class="font-semibold underline-offset-4 hover:underline">{{ $product->name }}</a>
                                <p class="text-xs text-subtle">{{ $product->sku }}</p>
                            </td>
                            <td class="px-5 py-4 text-muted">{{ $product->category->name }}</td>
                            <td class="px-5 py-4 text-muted">{{ $product->location?->code ?? '-' }}</td>
                            <td class="px-5 py-4">
                                <span class="font-semibold">{{ $product->stock }}</span> <span class="text-muted">{{ $product->unit }}</span>
                                <x-stock-badge :product="$product" class="ml-1" />
                            </td>
                            <td class="px-5 py-4 text-muted">{{ $product->min_stock }}</td>
                            @if (auth()->user()->isAdmin())
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a data-form-modal href="{{ route('products.edit', $product) }}" class="icon-btn" aria-label="Ubah {{ $product->name }}"><x-icon name="pencil" class="size-4" /></a>
                                        <form method="POST" action="{{ route('products.destroy', $product) }}"
                                              data-confirm="Produk {{ $product->name }} akan dihapus permanen." data-confirm-title="Hapus produk?" data-confirm-button="Ya, hapus">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn hover:bg-rose/20 hover:text-danger" aria-label="Hapus {{ $product->name }}"><x-icon name="trash" class="size-4" /></button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-muted">Tidak ada produk yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 flex justify-end">{{ $products->links('components.pagination') }}</div>
</x-layouts.app>
