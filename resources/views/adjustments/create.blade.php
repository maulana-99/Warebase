<x-layouts.app heading="Buat Penyesuaian Stok">
    <form method="POST" action="{{ route('adjustments.store') }}" class="card max-w-4xl" novalidate
          data-confirm="Stok sistem akan diganti dengan stok fisik yang Anda masukkan. Penyesuaian tidak bisa dihapus." data-confirm-title="Simpan penyesuaian?" data-confirm-button="Ya, sesuaikan">
        @csrf

        <div class="grid gap-5 md:grid-cols-2">
            {{-- Kept across validation errors so the stale-count check uses the moment the form was first opened. --}}
            <input type="hidden" name="counted_since" value="{{ old('counted_since', now()->format('Y-m-d H:i:s')) }}">
            <p class="text-sm text-muted md:col-span-2">Tanggal hitung: <span class="font-semibold text-ink">hari ini</span>. Isi segera setelah menghitung di rak. Jika ada barang masuk atau keluar setelah formulir ini dibuka, penyesuaian ditolak agar transaksi itu tidak terhapus.</p>

            <div class="md:col-span-2">
                <x-item-rows :products="$products" field="counted" field-label="Stok fisik" :min="0" />
                <p class="mt-2 text-xs text-subtle">Masukkan jumlah hasil hitung di rak, bukan selisihnya.</p>
            </div>

            <div class="md:col-span-2">
                <label for="note" class="label">Alasan</label>
                <textarea id="note" name="note" rows="2" maxlength="1000" required placeholder="Contoh: stock opname bulanan, 2 rim rusak terkena air" class="input"
                          @error('note') aria-invalid="true" aria-describedby="note-error" @enderror>{{ old('note') }}</textarea>
                <x-field-error name="note" />
            </div>
        </div>

        <div class="mt-8 flex flex-wrap justify-end gap-3">
            <a href="{{ route('adjustments.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-layouts.app>
