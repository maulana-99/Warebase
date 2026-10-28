{{-- Approve / reject panel. Shown only to an admin who did not file the request (checked again in StockService). --}}
<div class="mt-6 grid gap-4 border-t border-line pt-6">
    <form method="POST" action="{{ route('requests.approve', $transaction) }}"
          data-confirm="Stok semua barang di permintaan ini akan langsung dikurangi." data-confirm-title="Setujui permintaan?" data-confirm-button="Ya, setujui" data-confirm-tone="neutral">
        @csrf
        <button type="submit" class="btn-primary btn-success w-full justify-center"><x-icon name="check" class="size-4" /> Setujui</button>
    </form>

    <form method="POST" action="{{ route('requests.reject', $transaction) }}" novalidate
          data-confirm="Permintaan akan ditolak dan stok tidak berubah." data-confirm-title="Tolak permintaan?" data-confirm-button="Ya, tolak">
        @csrf
        <label for="reject_reason" class="label">Alasan penolakan</label>
        <textarea id="reject_reason" name="reject_reason" rows="2" maxlength="500" required class="input"
                  @error('reject_reason') aria-invalid="true" aria-describedby="reject_reason-error" @enderror>{{ old('reject_reason') }}</textarea>
        <x-field-error name="reject_reason" />
        <button type="submit" class="btn-primary btn-danger mt-3 w-full justify-center"><x-icon name="x" class="size-4" /> Tolak</button>
    </form>
</div>
