<x-layouts.app heading="Buat Permintaan Barang">
    <form method="POST" action="{{ route('requests.store') }}" class="card max-w-4xl" novalidate
          data-confirm="Permintaan akan dikirim ke admin. Stok baru berkurang setelah disetujui." data-confirm-title="Kirim permintaan?" data-confirm-button="Ya, kirim" data-confirm-tone="neutral">
        @csrf

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="department_id" class="label">Divisi</label>
                <select id="department_id" name="department_id" required class="input" @error('department_id') aria-invalid="true" aria-describedby="department_id-error" @enderror>
                    <option value="">Pilih divisi</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((int) old('department_id') === $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                <x-field-error name="department_id" />
            </div>

            <div>
                <label for="recipient_name" class="label">Nama penerima</label>
                <input id="recipient_name" name="recipient_name" type="text" value="{{ old('recipient_name') }}" required maxlength="100" autocomplete="off"
                       class="input" @error('recipient_name') aria-invalid="true" aria-describedby="recipient_name-error" @enderror>
                <x-field-error name="recipient_name" />
            </div>

            <div>
                <label for="transaction_date" class="label">Tanggal</label>
                <input id="transaction_date" name="transaction_date" type="date" value="{{ old('transaction_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required
                       class="input" @error('transaction_date') aria-invalid="true" aria-describedby="transaction_date-error" @enderror>
                <x-field-error name="transaction_date" />
            </div>

            <div class="md:col-span-2">
                <x-item-rows :products="$products" />
            </div>

            <div class="md:col-span-2">
                <label for="note" class="label">Keperluan (opsional)</label>
                <textarea id="note" name="note" rows="2" maxlength="1000" placeholder="Contoh: kebutuhan ujian akhir semester" class="input"
                          @error('note') aria-invalid="true" aria-describedby="note-error" @enderror>{{ old('note') }}</textarea>
                <x-field-error name="note" />
            </div>
        </div>

        <div class="mt-8 flex flex-wrap justify-end gap-3">
            <a href="{{ route('requests.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">Kirim permintaan</button>
        </div>
    </form>
</x-layouts.app>
