<x-layouts.app heading="Log Aktivitas">
    <form method="GET" role="search" class="mb-5 flex flex-wrap items-center gap-3">
        <div class="relative w-full sm:w-72">
            <label for="log-search" class="sr-only">Cari aktivitas</label>
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-subtle" />
            <input id="log-search" name="q" type="search" value="{{ $search }}" placeholder="Cari deskripsi atau IP…" class="input pl-11">
        </div>
        <div>
            <label for="log-user" class="sr-only">Pengguna</label>
            <select id="log-user" name="user" class="input" onchange="this.form.requestSubmit()">
                <option value="">Semua pengguna</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected($user->id === $userId)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="log-group" class="sr-only">Jenis aktivitas</label>
            <select id="log-group" name="group" class="input" onchange="this.form.requestSubmit()">
                <option value="">Semua aktivitas</option>
                @foreach ($groups as $key => $label)
                    <option value="{{ $key }}" @selected($key === $group)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-secondary">Terapkan</button>
        <p class="text-sm"><span class="font-semibold">{{ $logs->total() }}</span> <span class="text-muted">aktivitas</span></p>
    </form>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[52rem] text-left text-sm">
                <caption class="sr-only">Log aktivitas</caption>
                <thead class="bg-raised/60 text-xs tracking-wide text-muted uppercase">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">Waktu</th>
                        <th scope="col" class="px-5 py-3 font-medium">Pengguna</th>
                        <th scope="col" class="px-5 py-3 font-medium">Aktivitas</th>
                        <th scope="col" class="px-5 py-3 font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($logs as $log)
                        <tr class="align-top transition hover:bg-raised/40">
                            <td class="px-5 py-4 whitespace-nowrap text-muted">{{ $log->created_at->translatedFormat('j M Y, H:i:s') }}</td>
                            <td class="px-5 py-4">{{ $log->user?->name ?? 'Tamu' }}</td>
                            <td class="px-5 py-4">
                                <p @class(['font-medium', 'text-danger' => $log->action === 'auth.failed'])>{{ $log->description }}</p>
                                @foreach ($log->properties['changes'] ?? [] as $column => $change)
                                    <p class="text-xs text-subtle">{{ $column }}: {{ is_scalar($change['old']) ? $change['old'] : '-' }} → {{ is_scalar($change['new']) ? $change['new'] : '-' }}</p>
                                @endforeach
                                @isset($log->properties['reason'])
                                    <p class="text-xs text-subtle">Alasan: {{ $log->properties['reason'] }}</p>
                                @endisset
                            </td>
                            <td class="px-5 py-4 text-muted">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-muted">Belum ada aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 flex justify-end">{{ $logs->links('components.pagination') }}</div>
</x-layouts.app>
