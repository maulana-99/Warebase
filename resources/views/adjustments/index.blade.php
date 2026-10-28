<x-layouts.app heading="Penyesuaian Stok">
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <p class="flex max-w-xl items-start gap-2 rounded-2xl bg-sky/10 px-4 py-3 text-sm text-info"><x-icon name="scale" class="mt-0.5 size-4 shrink-0" /> Catat hasil hitung fisik (stock opname). Hanya produk yang jumlahnya berbeda dari stok sistem yang disesuaikan.</p>
        <a data-form-modal href="{{ route('adjustments.create') }}" class="btn-primary ml-auto"><x-icon name="plus" class="size-4" /> Buat penyesuaian</a>
    </div>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="table-stack w-full min-w-[44rem] text-left text-sm">
                <caption class="sr-only">Daftar penyesuaian stok</caption>
                <thead class="bg-raised/60 text-xs tracking-wide text-muted uppercase">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">Kode</th>
                        <th scope="col" class="px-5 py-3 font-medium">Tanggal</th>
                        <th scope="col" class="px-5 py-3 font-medium">Produk disesuaikan</th>
                        <th scope="col" class="px-5 py-3 font-medium">Alasan</th>
                        <th scope="col" class="px-5 py-3 font-medium">Oleh</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium"><span class="sr-only">Detail</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($transactions as $transaction)
                        <tr class="transition hover:bg-raised/40">
                            <td class="px-5 py-4 font-semibold">{{ $transaction->code }}</td>
                            <td class="px-5 py-4 text-muted">{{ $transaction->transaction_date->translatedFormat('j M Y') }}</td>
                            <td class="px-5 py-4">{{ $transaction->items_count }}</td>
                            <td class="max-w-xs truncate px-5 py-4 text-muted">{{ $transaction->note }}</td>
                            <td class="px-5 py-4 text-muted">{{ $transaction->creator->name }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('adjustments.show', $transaction) }}" class="btn-secondary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-muted">Belum ada penyesuaian stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 flex justify-end">{{ $transactions->links('components.pagination') }}</div>
</x-layouts.app>
