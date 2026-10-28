@php($editing = $product->exists)

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="sku" class="label">SKU</label>
        <input id="sku" name="sku" type="text" value="{{ old('sku', $product->sku) }}" required autofocus autocomplete="off" maxlength="50"
               class="input" @error('sku') aria-invalid="true" aria-describedby="sku-error" @enderror>
        <x-field-error name="sku" />
    </div>

    <div>
        <label for="name" class="label">Nama produk</label>
        <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" required autocomplete="off" maxlength="150"
               class="input" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        <x-field-error name="name" />
    </div>

    <div>
        <label for="category_id" class="label">Kategori</label>
        <select id="category_id" name="category_id" required class="input" @error('category_id') aria-invalid="true" aria-describedby="category_id-error" @enderror>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-field-error name="category_id" />
    </div>

    <div>
        <label for="location_id" class="label">Rak</label>
        <select id="location_id" name="location_id" class="input" @error('location_id') aria-invalid="true" aria-describedby="location_id-error" @enderror>
            <option value="">Belum ditentukan</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}" @selected((int) old('location_id', $product->location_id) === $location->id)>{{ $location->code }} - {{ $location->name }}</option>
            @endforeach
        </select>
        <x-field-error name="location_id" />
    </div>

    <div>
        <label for="unit" class="label">Satuan</label>
        <input id="unit" name="unit" type="text" value="{{ old('unit', $product->unit) }}" required autocomplete="off" maxlength="20" placeholder="pcs, rim, box…"
               class="input" @error('unit') aria-invalid="true" aria-describedby="unit-error" @enderror>
        <x-field-error name="unit" />
    </div>

    <div>
        <label for="min_stock" class="label">Stok minimum</label>
        <input id="min_stock" name="min_stock" type="number" min="0" max="1000000" value="{{ old('min_stock', $product->min_stock) }}" required
               class="input" aria-describedby="min_stock-hint" @error('min_stock') aria-invalid="true" @enderror>
        <p id="min_stock-hint" class="mt-1.5 text-xs text-subtle">Produk ditandai menipis saat stok sama dengan atau di bawah angka ini.</p>
        <x-field-error name="min_stock" />
    </div>

    <div class="md:col-span-2">
        <label for="description" class="label">Deskripsi</label>
        <textarea id="description" name="description" rows="3" maxlength="1000" class="input"
                  @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description', $product->description) }}</textarea>
        <x-field-error name="description" />
    </div>

    @if ($editing)
        <p class="text-sm text-muted md:col-span-2">Stok saat ini: <span class="font-semibold text-ink">{{ $product->stock }} {{ $product->unit }}</span>. Stok hanya berubah lewat barang masuk, barang keluar, atau penyesuaian.</p>
    @endif
</div>
