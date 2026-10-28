{{--
    One shared confirmation modal. Any form with data-confirm="message" opens it before submitting.
    Optional: data-confirm-title, data-confirm-button, data-confirm-tone="danger".
--}}
<dialog id="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-message"
        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-3xl bg-panel p-0 text-ink backdrop:bg-black/70">
    <div class="p-6">
        <span data-confirm-icon class="flex size-12 items-center justify-center rounded-full bg-rose/15 text-danger">
            <x-icon name="alert" class="size-6" />
        </span>
        <h2 id="confirm-title" class="mt-5 text-xl font-semibold">Apakah Anda yakin?</h2>
        <p id="confirm-message" class="mt-2 text-sm text-muted"></p>

        <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button type="button" class="btn-secondary" data-confirm-cancel autofocus>Batal</button>
            <button type="button" class="btn-primary" data-confirm-accept>Ya, lanjutkan</button>
        </div>
    </div>
</dialog>
