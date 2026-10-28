<x-layouts.app heading="Tambah Pengguna">
    <form method="POST" action="{{ route('users.store') }}" class="card max-w-3xl" novalidate>
        @csrf
        @include('users._form')
        <div class="mt-8 flex flex-wrap justify-end gap-3">
            <a href="{{ route('users.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-layouts.app>
