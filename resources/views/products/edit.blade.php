<x-layouts.app heading="Ubah Produk">
    <form method="POST" action="{{ route('products.update', $product) }}" class="card max-w-3xl" novalidate
          data-confirm="Perubahan data produk {{ $product->name }} akan disimpan." data-confirm-title="Simpan perubahan?" data-confirm-button="Ya, simpan" data-confirm-tone="neutral">
        @csrf
        @method('PUT')
        @include('products._form')
        <div class="mt-8 flex flex-wrap justify-end gap-3">
            <a href="{{ route('products.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">Simpan perubahan</button>
        </div>
    </form>
</x-layouts.app>
