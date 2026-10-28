<x-layouts.app heading="Kartu Stok">
    <div class="mb-5 grid gap-5 lg:grid-cols-[1fr_auto]">
        <section class="card">
            <p class="text-xs text-subtle">{{ $product->sku }} · {{ $product->category->name }} · Rak {{ $product->location?->code ?? '-' }}</p>
            <h2 class="mt-1 text-2xl font-semibold">{{ $product->name }}</h2>
            <p class="mt-3 text-sm text-muted">Stok sistem: <span class="text-lg font-semibold text-ink">{{ $product->stock }} {{ $product->unit }}</span> · Minimum {{ $product->min_stock }}</p>
        </section>

        <section @class(['card flex items-center gap-3 text-sm', 'text-success' => $balanced, 'text-danger' => ! $balanced]) role="status">
            <x-icon :name="$balanced ? 'check' : 'alert'" class="size-6 shrink-0" />
            <p class="max-w-xs">
                @if ($balanced)
                    Saldo kartu stok cocok dengan stok sistem.
                @else
                    Saldo kartu stok ({{ $lastBalance }}) berbeda dengan stok sistem ({{ $product->stock }}). Stok berubah di luar transaksi; periksa log aktivitas.
                @endif
            </p>
        </section>
    </div>

    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
        <div>
            <label for="from" class="label">Dari tanggal</label>
            <input id="from" name="from" type="date" value="{{ $from }}" class="input" @error('from') aria-invalid="true" aria-describedby="from-error" @enderror>
            <x-field-error name="from" />
        </div>
        <div>
            <label for="to" class="label">Sampai tanggal</label>
            <input id="to" name="to" type="date" value="{{ $to }}" class="input" @error('to') aria-invalid="true" aria-describedby="to-error" @enderror>
            <x-field-error name="to" />
        </div>
        <button type="submit" class="btn-secondary">Terapkan</button>
        @if ($from || $to)
            <a href="{{ route('products.stock-card', $product) }}" class="btn-secondary">Reset</a>
        @endif
        <a href="{{ route('products.index') }}" class="btn-secondary ml-auto">Kembali ke produk</a>
    </form>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[56rem] text-left text-sm">
                <caption class="sr-only">Riwayat pergerakan stok {{ $product->name }}</caption>
                <thead class="bg-raised/60 text-xs tracking-wide text-muted uppercase">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">Waktu proses</th>
                        <th scope="col" class="px-5 py-3 font-medium">Transaksi</th>
                        <th scope="col" class="px-5 py-3 font-medium">Keterangan</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium">Masuk</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium">Keluar</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($items as $item)
                        @php($delta = $item->stock_after - $item->stock_before)
                        @php($url = $item->transaction->detailUrlFor(auth()->user()))
                        <tr class="transition hover:bg-raised/40">
                            <td class="px-5 py-4 text-muted">{{ $item->transaction->approved_at->translatedFormat('j M Y, H:i') }}</td>
                            <td class="px-5 py-4">
                                @if ($url)
                                    <a href="{{ $url }}" class="font-semibold underline-offset-4 hover:underline">{{ $item->transaction->code }}</a>
                                @else
                                    <span class="font-semibold">{{ $item->transaction->code }}</span>
                                @endif
                                <p class="text-xs text-subtle">{{ $item->transaction->type->label() }} · {{ $item->transaction->creator->name }}</p>
                            </td>
                            <td class="max-w-xs truncate px-5 py-4 text-muted">
                                {{-- The free-text note is only shown to users who may open the transaction itself. --}}
                                {{ $item->transaction->supplier?->name ?? $item->transaction->department?->name ?? ($url ? $item->transaction->note : $item->transaction->type->label()) }}
                            </td>
                            <td class="px-5 py-4 text-right text-success">{{ $delta > 0 ? '+'.$delta : '' }}</td>
                            <td class="px-5 py-4 text-right text-danger">{{ $delta < 0 ? '−'.abs($delta) : '' }}</td>
                            <td class="px-5 py-4 text-right font-semibold">{{ $item->stock_after }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-muted">Belum ada pergerakan stok{{ $from || $to ? ' pada periode ini' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 flex justify-end">{{ $items->links('components.pagination') }}</div>
</x-layouts.app>
