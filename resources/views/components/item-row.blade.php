<div data-item-row class="grid grid-cols-[1fr_8rem_auto] items-start gap-3 rounded-2xl border border-line bg-raised/40 p-3">
    <div>
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
        <label for="items-{{ $index }}-quantity" class="sr-only">Jumlah</label>
        <input id="items-{{ $index }}-quantity" name="items[{{ $index }}][quantity]" type="number" min="1" max="1000000" required
               value="{{ $row['quantity'] ?? '' }}" placeholder="Jumlah" class="input"
               @error("items.$index.quantity") aria-invalid="true" @enderror>
        @error("items.$index.quantity")<p class="mt-1.5 text-xs text-danger">{{ $message }}</p>@enderror
    </div>
    <button type="button" data-item-remove class="icon-btn mt-1 hover:bg-rose/20 hover:text-danger" aria-label="Hapus baris"><x-icon name="trash" class="size-4" /></button>
</div>
