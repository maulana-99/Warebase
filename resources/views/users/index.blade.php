<x-layouts.app heading="Pengguna">
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <form method="GET" role="search" class="relative w-full sm:w-72">
            <label for="user-search" class="sr-only">Cari pengguna</label>
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-subtle" />
            <input id="user-search" name="q" type="search" value="{{ $search }}" placeholder="Cari nama atau email…" class="input pl-11">
        </form>
        <p class="text-sm"><span class="font-semibold">{{ $users->total() }}</span> <span class="text-muted">pengguna</span></p>
        <a data-form-modal href="{{ route('users.create') }}" class="btn-primary ml-auto"><x-icon name="plus" class="size-4" /> Tambah pengguna</a>
    </div>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="table-stack w-full min-w-[40rem] text-left text-sm">
                <thead class="bg-raised/60 text-xs tracking-wide text-muted uppercase">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">Nama</th>
                        <th scope="col" class="px-5 py-3 font-medium">Peran</th>
                        <th scope="col" class="px-5 py-3 font-medium">Transaksi dicatat</th>
                        <th scope="col" class="px-5 py-3 font-medium">Terdaftar</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($users as $user)
                        <tr class="transition hover:bg-raised/40">
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $user->name }} @if ($user->is(auth()->user()))<span class="text-xs font-normal text-subtle">(Anda)</span>@endif</p>
                                <p class="text-xs text-subtle">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span @class(['badge', 'bg-sky/15 text-info' => $user->isAdmin(), 'bg-raised text-muted' => ! $user->isAdmin()])>{{ $user->role->label() }}</span>
                            </td>
                            <td class="px-5 py-4">{{ $user->created_transactions_count }}</td>
                            <td class="px-5 py-4 text-muted">{{ $user->created_at->translatedFormat('j M Y') }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a data-form-modal href="{{ route('users.edit', $user) }}" class="icon-btn icon-btn-info" aria-label="Ubah {{ $user->name }}"><x-icon name="pencil" class="size-4" /></a>
                                    @unless ($user->is(auth()->user()))
                                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                                              data-confirm="Akun {{ $user->name }} akan dihapus permanen dan tidak bisa login lagi." data-confirm-title="Hapus pengguna?" data-confirm-button="Ya, hapus">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn icon-btn-danger" aria-label="Hapus {{ $user->name }}"><x-icon name="trash" class="size-4" /></button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-muted">Tidak ada pengguna yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 flex justify-end">{{ $users->links('components.pagination') }}</div>
</x-layouts.app>
