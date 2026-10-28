<x-layouts.app heading="Data Master">
    @include('master._tabs')

    <div class="mb-5 flex flex-wrap items-center gap-3">
        <form method="GET" role="search" class="relative w-full sm:w-72">
            <label for="master-search" class="sr-only">Cari {{ strtolower($config['title']) }}</label>
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-subtle" />
            <input id="master-search" name="q" type="search" value="{{ $search }}" placeholder="Cari {{ strtolower($config['title']) }}…" class="input pl-11">
        </form>
        <p class="text-sm"><span class="font-semibold">{{ $items->total() }}</span> <span class="text-muted">{{ strtolower($config['title']) }}</span></p>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('master.create', $type) }}" class="btn-primary ml-auto"><x-icon name="plus" class="size-4" /> Tambah {{ strtolower($config['singular']) }}</a>
        @endif
    </div>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <caption class="sr-only">Daftar {{ strtolower($config['title']) }}</caption>
                <thead class="bg-raised/60 text-xs tracking-wide text-muted uppercase">
                    <tr>
                        @foreach ($config['columns'] as $label)
                            <th scope="col" class="px-5 py-3 font-medium">{{ $label }}</th>
                        @endforeach
                        <th scope="col" class="px-5 py-3 font-medium">Dipakai</th>
                        @if (auth()->user()->isAdmin())
                            <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($items as $item)
                        <tr class="transition hover:bg-raised/40">
                            @foreach ($config['columns'] as $column => $label)
                                <td @class(['px-5 py-4', 'font-semibold' => $loop->first, 'max-w-xs truncate text-muted' => ! $loop->first])>{{ $item->{$column} ?: '-' }}</td>
                            @endforeach
                            <td class="px-5 py-4 text-muted">{{ $item->{$config['used_by'].'_count'} }} {{ $config['used_label'] }}</td>
                            @if (auth()->user()->isAdmin())
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('master.edit', [$type, $item->id]) }}" class="icon-btn" aria-label="Ubah {{ $item->name }}"><x-icon name="pencil" class="size-4" /></a>
                                        <form method="POST" action="{{ route('master.destroy', [$type, $item->id]) }}"
                                              data-confirm="{{ $config['singular'] }} {{ $item->name }} akan dihapus permanen." data-confirm-title="Hapus {{ strtolower($config['singular']) }}?" data-confirm-button="Ya, hapus">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn hover:bg-rose/20 hover:text-danger" aria-label="Hapus {{ $item->name }}"><x-icon name="trash" class="size-4" /></button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($config['columns']) + 2 }}" class="px-5 py-10 text-center text-muted">Belum ada data {{ strtolower($config['title']) }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 flex justify-end">{{ $items->links('components.pagination') }}</div>
</x-layouts.app>
