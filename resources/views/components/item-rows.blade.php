@props(['products'])

{{-- Repeatable product + quantity rows. resources/js/app.js adds and removes rows. --}}
@php
    // Old input comes from the client, so keep only scalar values (a crafted array must not break the page).
    $rows = collect(old('items', []))
        ->map(fn ($row) => is_array($row) ? array_map(fn ($value) => is_scalar($value) ? (string) $value : '', $row) : [])
        ->values()
        ->all() ?: [['product_id' => '', 'quantity' => '']];
@endphp

<fieldset data-items>
    <legend class="label">Daftar barang</legend>
    <x-field-error name="items" />

    <div data-item-list class="grid gap-3">
        @foreach ($rows as $i => $row)
            @include('components.item-row', ['index' => $i, 'row' => $row])
        @endforeach
    </div>

    <template data-item-template>
        @include('components.item-row', ['index' => '__INDEX__', 'row' => ['product_id' => '', 'quantity' => '']])
    </template>

    <button type="button" data-item-add class="btn-secondary mt-3"><x-icon name="plus" class="size-4" /> Tambah barang</button>
</fieldset>
