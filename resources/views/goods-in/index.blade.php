<x-layouts.app heading="Barang Masuk">
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <form method="GET" role="search" class="relative w-full sm:w-72">
            <label for="goods-in-search" class="sr-only">Cari barang masuk</label>
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-subtle" />
            <input id="goods-in-search" name="q" type="search" value="{{ $search }}" placeholder="Cari kode atau supplier…" class="input pl-11">
        </form>
        <p class="text-sm"><span class="font-semibold">{{ $transactions->total() }}</span> <span class="text-muted">transaksi</span></p>
        <a href="{{ route('goods-in.create') }}" class="btn-primary ml-auto"><x-icon name="plus" class="size-4" /> Catat barang masuk</a>
    </div>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <caption class="sr-only">Daftar barang masuk</caption>
                <thead class="bg-raised/60 text-xs tracking-wide text-muted uppercase">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">Kode</th>
                        <th scope="col" class="px-5 py-3 font-medium">Tanggal</th>
                        <th scope="col" class="px-5 py-3 font-medium">Supplier</th>
                        <th scope="col" class="px-5 py-3 font-medium">Barang</th>
                        <th scope="col" class="px-5 py-3 font-medium">Dicatat oleh</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium"><span class="sr-only">Detail</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($transactions as $transaction)
                        <tr class="transition hover:bg-raised/40">
                            <td class="px-5 py-4 font-semibold">{{ $transaction->code }}</td>
                            <td class="px-5 py-4 text-muted">{{ $transaction->transaction_date->translatedFormat('j M Y') }}</td>
                            <td class="px-5 py-4">{{ $transaction->supplier?->name }}</td>
                            <td class="px-5 py-4 text-muted">{{ $transaction->items_count }} jenis, {{ $transaction->items_sum_quantity }} unit</td>
                            <td class="px-5 py-4 text-muted">{{ $transaction->creator->name }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('goods-in.show', $transaction) }}" class="btn-secondary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-muted">Belum ada barang masuk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 flex justify-end">{{ $transactions->links('components.pagination') }}</div>
</x-layouts.app>
