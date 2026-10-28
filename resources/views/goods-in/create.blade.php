<x-layouts.app heading="Catat Barang Masuk">
    <form method="POST" action="{{ route('goods-in.store') }}" class="card max-w-4xl" novalidate
          data-confirm="Stok semua barang di daftar akan langsung bertambah. Transaksi yang sudah dicatat tidak bisa dihapus." data-confirm-title="Simpan barang masuk?" data-confirm-button="Ya, simpan" data-confirm-tone="neutral">
        @csrf

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="supplier_id" class="label">Supplier</label>
                <select id="supplier_id" name="supplier_id" required class="input" @error('supplier_id') aria-invalid="true" aria-describedby="supplier_id-error" @enderror>
                    <option value="">Pilih supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((int) old('supplier_id') === $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
                <x-field-error name="supplier_id" />
            </div>

            <div>
                <label for="transaction_date" class="label">Tanggal terima</label>
                <input id="transaction_date" name="transaction_date" type="date" value="{{ old('transaction_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required
                       class="input" @error('transaction_date') aria-invalid="true" aria-describedby="transaction_date-error" @enderror>
                <x-field-error name="transaction_date" />
            </div>

            <div class="md:col-span-2">
                <x-item-rows :products="$products" />
            </div>

            <div class="md:col-span-2">
                <label for="note" class="label">Catatan (opsional)</label>
                <textarea id="note" name="note" rows="2" maxlength="1000" placeholder="Nomor surat jalan, kondisi barang…" class="input"
                          @error('note') aria-invalid="true" aria-describedby="note-error" @enderror>{{ old('note') }}</textarea>
                <x-field-error name="note" />
            </div>
        </div>

        <div class="mt-8 flex flex-wrap justify-end gap-3">
            <a href="{{ route('goods-in.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-layouts.app>
