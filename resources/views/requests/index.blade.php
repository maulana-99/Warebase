<x-layouts.app heading="Permintaan Barang">
    <form method="GET" role="search" class="mb-5 flex flex-wrap items-center gap-3">
        <div class="relative w-full sm:w-72">
            <label for="request-search" class="sr-only">Cari permintaan</label>
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-subtle" />
            <input id="request-search" name="q" type="search" value="{{ $search }}" placeholder="Cari kode, penerima, divisi…" class="input pl-11">
        </div>
        <div>
            <label for="request-status" class="sr-only">Status</label>
            <select id="request-status" name="status" class="input" onchange="this.form.requestSubmit()">
                <option value="">Semua status</option>
                @foreach (\App\Enums\TransactionStatus::cases() as $option)
                    <option value="{{ $option->value }}" @selected($status === $option)>{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-secondary">Terapkan</button>
        <p class="text-sm"><span class="font-semibold">{{ $transactions->total() }}</span> <span class="text-muted">permintaan</span></p>
        <a data-form-modal href="{{ route('requests.create') }}" class="btn-primary ml-auto"><x-icon name="plus" class="size-4" /> Buat permintaan</a>
    </form>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[52rem] text-left text-sm">
                <caption class="sr-only">Daftar permintaan barang keluar</caption>
                <thead class="bg-raised/60 text-xs tracking-wide text-muted uppercase">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">Kode</th>
                        <th scope="col" class="px-5 py-3 font-medium">Tanggal</th>
                        <th scope="col" class="px-5 py-3 font-medium">Untuk</th>
                        <th scope="col" class="px-5 py-3 font-medium">Barang</th>
                        @if (auth()->user()->isAdmin())
                            <th scope="col" class="px-5 py-3 font-medium">Diajukan oleh</th>
                        @endif
                        <th scope="col" class="px-5 py-3 font-medium">Status</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium"><span class="sr-only">Detail</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($transactions as $transaction)
                        <tr class="transition hover:bg-raised/40">
                            <td class="px-5 py-4 font-semibold">{{ $transaction->code }}</td>
                            <td class="px-5 py-4 text-muted">{{ $transaction->transaction_date->translatedFormat('j M Y') }}</td>
                            <td class="px-5 py-4">
                                <p>{{ $transaction->department?->name }}</p>
                                <p class="text-xs text-subtle">{{ $transaction->recipient_name }}</p>
                            </td>
                            <td class="px-5 py-4 text-muted">{{ $transaction->items_count }} jenis, {{ $transaction->items_sum_quantity }} unit</td>
                            @if (auth()->user()->isAdmin())
                                <td class="px-5 py-4 text-muted">{{ $transaction->creator->name }}</td>
                            @endif
                            <td class="px-5 py-4"><x-status-badge :status="$transaction->status" /></td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('requests.show', $transaction) }}" class="btn-secondary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-muted">Belum ada permintaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 flex justify-end">{{ $transactions->links('components.pagination') }}</div>
</x-layouts.app>
