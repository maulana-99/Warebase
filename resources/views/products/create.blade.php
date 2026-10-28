<x-layouts.app heading="Tambah Produk">
    <form method="POST" action="{{ route('products.store') }}" class="card max-w-3xl" novalidate>
        @csrf
        @include('products._form')
        <div class="mt-8 flex flex-wrap justify-end gap-3">
            <a href="{{ route('products.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-layouts.app>
