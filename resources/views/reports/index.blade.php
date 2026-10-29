<x-layouts.app heading="Laporan">
    @php($query = ['from' => $from->toDateString(), 'to' => $to->toDateString()])

    <nav aria-label="Jenis laporan" class="mb-5 flex flex-wrap gap-2 print:hidden">
        @foreach (['stock' => 'Pergerakan stok', 'staff' => 'Jejak kerja'] as $key => $label)
            <a href="{{ route('reports.index', $query + ['tab' => $key]) }}" @if ($key === $tab) aria-current="page" @endif
               @class(['badge px-4 py-2 text-sm transition', 'bg-sky text-on-accent' => $key === $tab, 'bg-raised text-muted hover:text-ink' => $key !== $tab])>{{ $label }}</a>
        @endforeach
    </nav>

    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3 print:hidden">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div>
            <label for="from" class="label">Dari tanggal</label>
            <input id="from" name="from" type="date" value="{{ $from->toDateString() }}" class="input" @error('from') aria-invalid="true" aria-describedby="from-error" @enderror>
            <x-field-error name="from" />
        </div>
        <div>
            <label for="to" class="label">Sampai tanggal</label>
            <input id="to" name="to" type="date" value="{{ $to->toDateString() }}" class="input" @error('to') aria-invalid="true" aria-describedby="to-error" @enderror>
            <x-field-error name="to" />
        </div>
        @if ($tab === 'stock')
            <div>
                <label for="category" class="label">Kategori</label>
                <select id="category" name="category" class="input">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($category->id === $categoryId)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <button type="submit" class="btn-secondary">Terapkan</button>
        <div class="ml-auto flex gap-2">
            @if ($tab === 'stock')
                <a href="{{ route('reports.stock-csv', $query + ['category' => $categoryId]) }}" class="btn-secondary">Unduh CSV</a>
            @endif
            <button type="button" class="btn-primary" onclick="window.print()">Cetak</button>
        </div>
    </form>

    <p class="mb-3 text-sm text-muted">Periode {{ $from->translatedFormat('j F Y') }} – {{ $to->translatedFormat('j F Y') }}</p>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            @if ($tab === 'stock')
                <table class="table-stack w-full min-w-[56rem] text-left text-sm">
                    <caption class="sr-only">Pergerakan stok per produk</caption>
                    <thead class="bg-raised/60 text-xs tracking-wide text-muted uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Produk</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Stok awal</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Masuk</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Keluar</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Penyesuaian</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Stok akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($products as $product)
                            <tr>
                                <td class="px-5 py-3">
                                    <a href="{{ route('products.stock-card', ['product' => $product] + $query) }}" class="font-semibold underline-offset-4 hover:underline">{{ $product->name }}</a>
                                    <p class="text-xs text-subtle">{{ $product->sku }} · {{ $product->category->name }}</p>
                                </td>
                                <td class="px-5 py-3 text-right">{{ $product->opening }}</td>
                                <td @class(['px-5 py-3 text-right', 'text-success' => $product->qty_in, 'text-subtle' => ! $product->qty_in])>{{ $product->qty_in ?: '–' }}</td>
                                <td @class(['px-5 py-3 text-right', 'text-danger' => $product->qty_out, 'text-subtle' => ! $product->qty_out])>{{ $product->qty_out ?: '–' }}</td>
                                <td @class(['px-5 py-3 text-right', 'text-subtle' => ! $product->qty_adjustment])>{{ $product->qty_adjustment ? ($product->qty_adjustment > 0 ? '+' : '−').abs($product->qty_adjustment) : '–' }}</td>
                                <td class="px-5 py-3 text-right font-semibold">{{ $product->closing }} <span class="font-normal text-muted">{{ $product->unit }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-muted">Tidak ada produk.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($products->isNotEmpty())
                        <tfoot class="border-t border-line font-semibold">
                            <tr>
                                <th scope="row" class="px-5 py-3 text-left">Total unit</th>
                                <td class="px-5 py-3 text-right">{{ $products->sum('opening') }}</td>
                                <td class="px-5 py-3 text-right text-success">{{ $products->sum('qty_in') }}</td>
                                <td class="px-5 py-3 text-right text-danger">{{ $products->sum('qty_out') }}</td>
                                <td class="px-5 py-3 text-right">{{ $products->sum('qty_adjustment') }}</td>
                                <td class="px-5 py-3 text-right">{{ $products->sum('closing') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            @else
                <table class="table-stack w-full min-w-[56rem] text-left text-sm">
                    <caption class="sr-only">Jejak kerja per pengguna</caption>
                    <thead class="bg-raised/60 text-xs tracking-wide text-muted uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Pengguna</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Barang masuk</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Permintaan</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Disetujui</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Ditolak</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Penyesuaian</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Aktivitas</th>
                            <th scope="col" class="px-5 py-3 font-medium">Terakhir aktif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="font-semibold">{{ $user->name }}</p>
                                    <p class="text-xs text-subtle">{{ $user->role->label() }}</p>
                                </td>
                                <td class="px-5 py-3 text-right">{{ $user->goods_in_count }}</td>
                                <td class="px-5 py-3 text-right">{{ $user->requests_count }}</td>
                                <td class="px-5 py-3 text-right">{{ $user->approved_count }}</td>
                                <td class="px-5 py-3 text-right">{{ $user->rejected_count }}</td>
                                <td class="px-5 py-3 text-right">{{ $user->adjustments_count }}</td>
                                <td class="px-5 py-3 text-right">
                                    @if (auth()->user()->isAdmin() && $user->activity_count)
                                        <a href="{{ route('activity-logs.index', ['user' => $user->id]) }}" class="underline-offset-4 hover:underline">{{ $user->activity_count }}</a>
                                    @else
                                        {{ $user->activity_count }}
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-muted">{{ $user->last_activity ? \Illuminate\Support\Carbon::parse($user->last_activity)->translatedFormat('j M Y, H:i') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-layouts.app>
