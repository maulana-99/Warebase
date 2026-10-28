@props(['title' => null, 'heading' => null])

@php
    $user = auth()->user();

    // Menu entries appear only once their route exists, so the sidebar grows with each feature.
    $menu = collect([
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
        ['route' => 'products.index', 'label' => 'Produk', 'icon' => 'box'],
        ['route' => 'goods-in.index', 'label' => 'Barang Masuk', 'icon' => 'arrow-down-to-line'],
        ['route' => 'requests.index', 'label' => 'Permintaan Barang', 'icon' => 'clipboard-list'],
        ['route' => 'adjustments.index', 'label' => 'Penyesuaian Stok', 'icon' => 'scale', 'admin' => true],
        ['route' => 'master.index', 'label' => 'Data Master', 'icon' => 'layout-grid'],
        ['route' => 'reports.index', 'label' => 'Laporan', 'icon' => 'chart'],
        ['route' => 'users.index', 'label' => 'Pengguna', 'icon' => 'users', 'admin' => true],
        ['route' => 'activity-logs.index', 'label' => 'Log Aktivitas', 'icon' => 'history', 'admin' => true],
    ])->filter(fn ($item) => Route::has($item['route']) && (empty($item['admin']) || $user->isAdmin()));

    $isActive = fn ($item) => request()->routeIs(str_replace('.index', '', $item['route']).'*');
@endphp

<x-layouts.base :title="$title ?? $heading" class="p-0 md:p-6">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-xl focus:bg-ink focus:px-4 focus:py-2 focus:text-shell">Lewati ke konten</a>

    <div data-app-shell class="flex min-h-screen bg-shell md:min-h-[calc(100vh-3rem)] md:rounded-[2rem]">
        {{-- Desktop: icon rail. --}}
        <aside class="sticky top-0 hidden print:hidden h-screen w-24 shrink-0 flex-col items-center border-r border-line/60 py-6 md:flex md:h-[calc(100vh-3rem)]">
            <a href="{{ route('dashboard') }}" class="mb-10 text-ink" aria-label="Warebase, ke dashboard">
                <x-logo />
            </a>

            <nav aria-label="Menu utama" class="flex flex-1 flex-col items-center gap-3">
                @foreach ($menu as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'group relative flex size-11 items-center justify-center rounded-2xl transition duration-150 active:scale-90',
                           'bg-ink text-shell' => $isActive($item),
                           'text-muted hover:bg-raised hover:text-ink' => ! $isActive($item),
                       ])
                       @if ($isActive($item)) aria-current="page" @endif>
                        <x-icon :name="$item['icon']" />
                        <span class="sr-only">{{ $item['label'] }}</span>
                        <span aria-hidden="true" class="pointer-events-none absolute left-full z-20 ml-3 whitespace-nowrap rounded-xl bg-raised px-3 py-1.5 text-xs font-medium text-ink opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <form method="POST" action="{{ route('logout') }}" data-confirm="Sesi Anda akan diakhiri. Pekerjaan yang belum disimpan akan hilang." data-confirm-title="Keluar dari Warebase?" data-confirm-button="Ya, keluar">
                @csrf
                <button type="submit" class="flex size-11 items-center justify-center rounded-2xl bg-raised text-ink transition duration-150 hover:bg-rose/20 hover:text-danger active:scale-90" aria-label="Keluar">
                    <x-icon name="logout" />
                </button>
            </form>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Top bar. --}}
            <header class="flex items-center gap-3 px-4 pt-4 print:hidden sm:px-6 md:pt-6">
                <button type="button" class="icon-btn md:hidden" data-drawer-open aria-label="Buka menu" aria-controls="mobile-menu" aria-expanded="false">
                    <x-icon name="menu" />
                </button>

                <form action="{{ Route::has('products.index') ? route('products.index') : '#' }}" method="GET" role="search" class="relative hidden max-w-xs flex-1 sm:block">
                    <label for="global-search" class="sr-only">Cari produk</label>
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-subtle" />
                    <input id="global-search" name="q" type="search" placeholder="Cari produk…" class="input pl-11">
                </form>

                <p class="hidden text-sm font-semibold lg:block">{{ now()->translatedFormat('l, j F Y') }}</p>

                <div class="ml-auto flex items-center gap-3">
                    <x-theme-toggle />
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold">{{ $user->name }}</p>
                        <p class="text-xs text-muted">{{ $user->role->label() }}</p>
                    </div>
                    <span class="flex size-11 items-center justify-center rounded-full bg-sky text-sm font-bold text-on-accent" aria-hidden="true">
                        {{ str($user->name)->explode(' ')->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->join('') }}
                    </span>
                </div>
            </header>

            <main id="main" tabindex="-1" class="flex-1 px-4 pt-6 pb-10 sm:px-6">
                @if ($heading)
                    <h1 class="mb-6 text-3xl font-bold sm:text-4xl">{{ $heading }}</h1>
                @endif

                @if (session('success'))
                    <div role="status" class="mb-6 flex items-center gap-3 rounded-2xl bg-mint/15 px-4 py-3 text-sm text-success">
                        <x-icon name="check" class="size-4" /> {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div role="alert" class="mb-6 flex items-center gap-3 rounded-2xl bg-rose/15 px-4 py-3 text-sm text-danger">
                        <x-icon name="alert" class="size-4" /> {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <x-form-modal />
    <x-confirm-dialog />

    {{-- Mobile drawer. --}}
    <div id="mobile-menu" class="fixed inset-0 z-40 hidden md:hidden" data-drawer role="dialog" aria-modal="true" aria-label="Menu">
        <div class="absolute inset-0 bg-black/70" data-drawer-close></div>
        <nav aria-label="Menu seluler" class="relative flex h-full w-72 max-w-[85vw] flex-col bg-shell p-5">
            <div class="mb-8 flex items-center justify-between">
                <span class="flex items-center gap-3 text-lg font-bold"><x-logo class="size-8" /> Warebase</span>
                <button type="button" class="icon-btn" data-drawer-close aria-label="Tutup menu"><x-icon name="x" /></button>
            </div>
            <div class="flex flex-1 flex-col gap-1">
                @foreach ($menu as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition active:scale-[0.98]',
                           'bg-ink text-shell' => $isActive($item),
                           'text-muted hover:bg-raised hover:text-ink' => ! $isActive($item),
                       ])
                       @if ($isActive($item)) aria-current="page" @endif>
                        <x-icon :name="$item['icon']" /> {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
            <form method="POST" action="{{ route('logout') }}" data-confirm="Sesi Anda akan diakhiri. Pekerjaan yang belum disimpan akan hilang." data-confirm-title="Keluar dari Warebase?" data-confirm-button="Ya, keluar">
                @csrf
                <button type="submit" class="btn-secondary w-full"><x-icon name="logout" /> Keluar</button>
            </form>
        </nav>
    </div>
</x-layouts.base>
