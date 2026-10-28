{{--
    One shared modal for create/edit forms. Links with data-form-modal load their page's <form> into it
    (resources/js/app.js). Without JavaScript the links simply open the full form page.
--}}
<dialog id="form-modal" aria-labelledby="form-modal-title"
        class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-3xl overflow-y-auto rounded-3xl bg-panel p-0 text-ink backdrop:bg-black/70">
    <div class="p-6">
        <div class="mb-5 flex items-start justify-between gap-4">
            <h2 id="form-modal-title" class="text-xl font-semibold"></h2>
            <button type="button" class="icon-btn" data-form-modal-close aria-label="Tutup"><x-icon name="x" class="size-4" /></button>
        </div>
        <p data-form-modal-error role="alert" class="mb-4 hidden rounded-2xl bg-rose/15 px-4 py-3 text-sm text-danger"></p>
        <div data-form-modal-body></div>
    </div>
</dialog>
