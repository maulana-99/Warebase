@props(['title' => null])

<x-layouts.base :title="$title" class="flex min-h-screen items-center justify-center p-4">
    <x-theme-toggle class="fixed top-4 right-4" />

    <main class="w-full max-w-md rounded-[2rem] bg-shell p-6 sm:p-10">
        <div class="mb-8 flex items-center gap-3">
            <x-logo class="size-9 text-ink" />
            <span class="text-xl font-bold">Warebase</span>
        </div>

        {{ $slot }}
    </main>
</x-layouts.base>
