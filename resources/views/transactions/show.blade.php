<x-layouts.app :heading="$transaction->type->label().' '.$transaction->code">
    <div class="grid gap-5 lg:grid-cols-[1fr_20rem]">
        <section class="card overflow-hidden p-0">
            <h2 class="px-5 pt-5 text-lg font-semibold">Daftar barang</h2>
            <div class="overflow-x-auto">
                <table class="table-stack mt-3 w-full min-w-[36rem] text-left text-sm">
                    <thead class="bg-raised/60 text-xs tracking-wide text-muted uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium">Produk</th>
                            <th scope="col" class="px-5 py-3 font-medium">Jumlah</th>
                            @if ($transaction->isPending())
                                <th scope="col" class="px-5 py-3 font-medium">Stok saat ini</th>
                            @else
                                <th scope="col" class="px-5 py-3 font-medium">Stok sebelum</th>
                                <th scope="col" class="px-5 py-3 font-medium">Stok sesudah</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($transaction->items as $item)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ $item->product->name }}</p>
                                    <p class="text-xs text-subtle">{{ $item->product->sku }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($transaction->type === \App\Enums\TransactionType::Adjustment)
                                        {{-- Adjustments store the absolute difference; the sign comes from before/after. --}}
                                        <span @class(['font-semibold', 'text-success' => $item->stock_after > $item->stock_before, 'text-danger' => $item->stock_after < $item->stock_before])>
                                            {{ $item->stock_after > $item->stock_before ? '+' : '−' }}{{ $item->quantity }}
                                        </span> {{ $item->product->unit }}
                                    @else
                                        {{ $item->quantity }} {{ $item->product->unit }}
                                    @endif
                                </td>
                                @if ($transaction->isPending())
                                    <td class="px-5 py-4">
                                        {{ $item->product->stock }}
                                        @if ($item->product->stock < $item->quantity)
                                            <span class="badge ml-1 bg-rose/15 text-danger">Tidak cukup</span>
                                        @endif
                                    </td>
                                @else
                                    <td class="px-5 py-4 text-muted">{{ $item->stock_before ?? '-' }}</td>
                                    <td class="px-5 py-4 font-semibold">{{ $item->stock_after ?? '-' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="card">
            <dl class="grid gap-4 text-sm">
                <div><dt class="text-muted">Jenis</dt><dd class="mt-1"><x-type-badge :type="$transaction->type" /></dd></div>
                <div><dt class="text-muted">Status</dt><dd class="mt-1"><x-status-badge :status="$transaction->status" /></dd></div>
                <div><dt class="text-muted">Tanggal</dt><dd class="mt-1 font-semibold">{{ $transaction->transaction_date->translatedFormat('l, j F Y') }}</dd></div>
                @if ($transaction->supplier)
                    <div><dt class="text-muted">Supplier</dt><dd class="mt-1 font-semibold">{{ $transaction->supplier->name }}</dd></div>
                @endif
                @if ($transaction->department)
                    <div><dt class="text-muted">Untuk</dt><dd class="mt-1 font-semibold">{{ $transaction->department->name }}</dd><dd class="text-xs text-subtle">Penerima: {{ $transaction->recipient_name }}</dd></div>
                @endif
                <div><dt class="text-muted">Dicatat oleh</dt><dd class="mt-1 font-semibold">{{ $transaction->creator->name }}</dd><dd class="text-xs text-subtle">{{ $transaction->created_at->translatedFormat('j M Y, H:i') }}</dd></div>
                @if ($transaction->approver)
                    <div><dt class="text-muted">{{ $transaction->status === \App\Enums\TransactionStatus::Rejected ? 'Ditolak oleh' : 'Disetujui oleh' }}</dt><dd class="mt-1 font-semibold">{{ $transaction->approver->name }}</dd><dd class="text-xs text-subtle">{{ $transaction->approved_at->translatedFormat('j M Y, H:i') }}</dd></div>
                @endif
                @if ($transaction->reject_reason)
                    <div><dt class="text-muted">Alasan penolakan</dt><dd class="mt-1 whitespace-pre-line text-danger">{{ $transaction->reject_reason }}</dd></div>
                @endif
                @if ($transaction->note)
                    <div><dt class="text-muted">{{ $transaction->type === \App\Enums\TransactionType::Adjustment ? 'Alasan' : 'Catatan' }}</dt><dd class="mt-1 whitespace-pre-line">{{ $transaction->note }}</dd></div>
                @endif
            </dl>
            @if ($canDecide ?? false)
                @include('requests._decision')
            @elseif ($transaction->isPending() && auth()->user()->isAdmin())
                <p class="mt-6 rounded-2xl bg-sun/15 px-4 py-3 text-sm text-warning">Ini permintaan Anda sendiri. Admin lain yang harus memutuskannya.</p>
            @endif
            <a href="{{ $backRoute }}" class="btn-secondary mt-6 w-full justify-center">Kembali</a>
        </aside>
    </div>
</x-layouts.app>
