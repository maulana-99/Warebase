<div data-item-row class="grid grid-cols-[1fr_auto] items-start gap-3 rounded-2xl border border-line bg-raised/40 p-3 sm:grid-cols-[1fr_8rem_auto]">
    {{-- Phones: product on its own line, quantity and delete below. --}}
    <div class="col-span-2 sm:col-span-1">
        <label for="items-{{ $index }}-product" class="sr-only">Produk</label>
        <select id="items-{{ $index }}-product" name="items[{{ $index }}][product_id]" required class="input"
                @error("items.$index.product_id") aria-invalid="true" @enderror>
            <option value="">Pilih produk</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected((string) ($row['product_id'] ?? '') === (string) $product->id)>
                    {{ $product->sku }} · {{ $product->name }} (stok {{ $product->stock }} {{ $product->unit }})
                </option>
            @endforeach
        </select>
        @error("items.$index.product_id")<p class="mt-1.5 text-xs text-danger">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="items-{{ $index }}-{{ $field }}" class="sr-only">{{ $fieldLabel }}</label>
        <input id="items-{{ $index }}-{{ $field }}" name="items[{{ $index }}][{{ $field }}]" type="number" min="{{ $min }}" max="1000000" required
               value="{{ $row[$field] ?? '' }}" placeholder="{{ $fieldLabel }}" class="input"
               @error("items.$index.$field") aria-invalid="true" @enderror>
        @error("items.$index.$field")<p class="mt-1.5 text-xs text-danger">{{ $message }}</p>@enderror
    </div>
    <button type="button" data-item-remove class="icon-btn mt-1 hover:bg-rose/20 hover:text-danger" aria-label="Hapus baris"><x-icon name="trash" class="size-4" /></button>
</div>
