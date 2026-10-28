@php
    $maxMovement = max(1, collect($movement)->flatMap(fn ($d) => [$d['in'], $d['out']])->max());
    $totalCategoryProducts = max(1, collect($categories)->sum('count'));
    $gradient = collect($categories)->reduce(function ($carry, $c) use ($totalCategoryProducts) {
        $end = $carry['at'] + $c['count'] / $totalCategoryProducts * 100;
        $carry['stops'][] = "{$c['color']} {$carry['at']}% {$end}%";
        $carry['at'] = $end;

        return $carry;
    }, ['at' => 0, 'stops' => []])['stops'];
    $firstName = str(auth()->user()->name)->before(' ');
@endphp

<x-layouts.app title="Dashboard">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold sm:text-4xl">Halo, {{ $firstName }}! <span aria-hidden="true">👋</span></h1>
            <p class="mt-2 text-sm text-muted">Ini ringkasan kondisi gudang hari ini.</p>
        </div>
        <span class="inline-flex items-center gap-2 rounded-2xl bg-raised px-4 py-2 text-sm font-medium lg:hidden">
            <x-icon name="calendar" class="size-4" /> {{ now()->translatedFormat('j F Y') }}
        </span>
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
        {{-- Stat cards. --}}
        <section aria-label="Ringkasan" class="grid gap-4 sm:grid-cols-2">
            <x-stat-card label="Total produk" :value="number_format($stats['products'], 0, ',', '.')" hint="Jenis barang terdaftar" highlight
                         :href="Route::has('products.index') ? route('products.index') : null" />
            <x-stat-card label="Stok menipis" :value="$stats['lowStock']" hint="Stok di bawah batas minimum"
                         :tone="$stats['lowStock'] > 0 ? 'danger' : 'success'"
                         :href="Route::has('products.index') ? route('products.index', ['filter' => 'low']) : null">
                {{ $stats['lowStock'] > 0 ? 'Perlu restok' : 'Aman' }}
            </x-stat-card>
            <x-stat-card label="Menunggu persetujuan" :value="$stats['pendingRequests']" hint="Permintaan barang keluar"
                         :tone="$stats['pendingRequests'] > 0 ? 'warning' : null"
                         :href="Route::has('requests.index') ? route('requests.index', ['status' => 'pending']) : null">
                Butuh aksi
            </x-stat-card>
            <x-stat-card label="Transaksi bulan ini" :value="$stats['transactionsThisMonth']" hint="Dibanding bulan lalu"
                         :change="$stats['transactionsChange']"
                         :href="Route::has('reports.index') ? route('reports.index') : null" />
        </section>

        {{-- Movement chart. --}}
        <section class="card flex flex-col" aria-labelledby="movement-title">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                <h2 id="movement-title" class="text-lg font-semibold">Pergerakan Barang</h2>
                <p class="text-xs text-subtle">7 hari terakhir</p>
                <div class="ml-auto flex items-center gap-4 text-xs text-muted">
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-sky"></span> Masuk</span>
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-peach"></span> Keluar</span>
                </div>
            </div>

            <div class="sr-only">
            <table>
                <caption>Jumlah barang masuk dan keluar per hari, 7 hari terakhir</caption>
                <thead><tr><th scope="col">Tanggal</th><th scope="col">Masuk</th><th scope="col">Keluar</th></tr></thead>
                <tbody>
                    @foreach ($movement as $day)
                        <tr><th scope="row">{{ $day['date']->translatedFormat('j F') }}</th><td>{{ $day['in'] }}</td><td>{{ $day['out'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            <div class="mt-6 flex min-h-56 flex-1 items-end gap-2 border-b border-dashed border-line sm:gap-4" aria-hidden="true">
                @foreach ($movement as $day)
                    <div class="flex h-full flex-1 flex-col justify-end">
                        <div class="flex h-full items-end justify-center gap-1">
                            <div class="group relative w-full max-w-5 rounded-t-lg bg-sky" style="height: {{ max(2, $day['in'] / $maxMovement * 100) }}%">
                                <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 rounded-lg bg-raised px-2 py-1 text-xs whitespace-nowrap opacity-0 group-hover:opacity-100">{{ $day['in'] }}</span>
                            </div>
                            <div class="group relative w-full max-w-5 rounded-t-lg bg-peach" style="height: {{ max(2, $day['out'] / $maxMovement * 100) }}%">
                                <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 rounded-lg bg-raised px-2 py-1 text-xs whitespace-nowrap opacity-0 group-hover:opacity-100">{{ $day['out'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-3 flex gap-2 sm:gap-4" aria-hidden="true">
                @foreach ($movement as $day)
                    <p class="flex-1 text-center text-xs text-subtle">{{ $day['date']->translatedFormat('j M') }}</p>
                @endforeach
            </div>
        </section>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
        {{-- Attention cards. --}}
        <section aria-label="Perlu perhatian" class="grid gap-4 sm:grid-cols-2">
            <div class="card flex min-h-56 flex-col">
                <span class="flex size-11 items-center justify-center rounded-full bg-raised"><x-icon name="clipboard-list" /></span>
                <p class="mt-auto text-5xl font-semibold">{{ $stats['pendingRequests'] }} <span class="text-2xl font-medium">permintaan</span></p>
                <p class="mt-2 text-sm text-muted">{{ $stats['pendingRequests'] }} permintaan <span class="text-danger">sedang menunggu</span> persetujuan admin.</p>
            </div>
            <div class="card flex min-h-56 flex-col">
                <span class="flex size-11 items-center justify-center rounded-full bg-raised"><x-icon name="alert" /></span>
                <p class="mt-auto text-5xl font-semibold">{{ $stats['lowStock'] }} <span class="text-2xl font-medium">produk</span></p>
                <p class="mt-2 text-sm text-muted">{{ $stats['lowStock'] }} produk <span class="text-danger">hampir habis</span> dan perlu dipesan ulang.</p>
            </div>
        </section>

        {{-- Category donut. --}}
        <section class="card" aria-labelledby="category-title">
            <h2 id="category-title" class="text-lg font-semibold">Produk per Kategori</h2>
            @if ($categories)
                <div class="mt-6 flex flex-col items-center gap-8 sm:flex-row">
                    <div class="relative size-40 shrink-0 rounded-full" style="background: conic-gradient({{ implode(', ', $gradient) }})" role="img"
                         aria-label="Diagram proporsi produk per kategori">
                        <div class="absolute inset-7 flex flex-col items-center justify-center rounded-full bg-panel">
                            <span class="text-2xl font-semibold">{{ $totalCategoryProducts }}</span>
                            <span class="text-xs text-subtle">produk</span>
                        </div>
                    </div>
                    <ul class="w-full space-y-3 text-sm">
                        @foreach ($categories as $category)
                            <li class="flex items-center gap-3">
                                <span class="size-2.5 shrink-0 rounded-full" style="background: {{ $category['color'] }}"></span>
                                <span class="flex-1">{{ $category['name'] }}</span>
                                <span class="text-muted">{{ $category['count'] }} · {{ round($category['count'] / $totalCategoryProducts * 100) }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="mt-6 text-sm text-muted">Belum ada produk.</p>
            @endif
        </section>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        {{-- Recent transactions. --}}
        <section class="card overflow-hidden p-0" aria-labelledby="recent-title">
            <h2 id="recent-title" class="px-5 pt-5 text-lg font-semibold">Transaksi Terbaru</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[36rem] text-left text-sm">
                    <thead class="bg-raised/60 text-xs tracking-wide text-muted uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Kode</th>
                            <th scope="col" class="px-5 py-3 font-medium">Jenis</th>
                            <th scope="col" class="px-5 py-3 font-medium">Pihak</th>
                            <th scope="col" class="px-5 py-3 font-medium">Jumlah</th>
                            <th scope="col" class="px-5 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($recentTransactions as $transaction)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ $transaction->code }}</p>
                                    <p class="text-xs text-subtle">{{ $transaction->transaction_date->translatedFormat('j M Y') }} · {{ $transaction->creator->name }}</p>
                                </td>
                                <td class="px-5 py-4">{{ $transaction->type->label() }}</td>
                                <td class="px-5 py-4">{{ $transaction->supplier?->name ?? $transaction->department?->name ?? '—' }}</td>
                                <td class="px-5 py-4">{{ (int) $transaction->items_sum_quantity }}</td>
                                <td class="px-5 py-4"><x-status-badge :status="$transaction->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-muted">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Low stock list. --}}
        <section class="card" aria-labelledby="low-title">
            <h2 id="low-title" class="text-lg font-semibold">Stok Menipis</h2>
            <ul class="mt-4 divide-y divide-line">
                @forelse ($lowStockProducts as $product)
                    <li class="flex items-center gap-3 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ $product->name }}</p>
                            <p class="text-xs text-subtle">{{ $product->sku }} · Rak {{ $product->location?->code ?? '—' }}</p>
                        </div>
                        <p class="text-right text-sm">
                            <span class="font-semibold text-danger">{{ $product->stock }}</span>
                            <span class="text-subtle">/ {{ $product->min_stock }} {{ $product->unit }}</span>
                        </p>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-muted">Semua stok aman.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.app>
