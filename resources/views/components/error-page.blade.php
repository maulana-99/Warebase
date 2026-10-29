@props(['code', 'title', 'message'])

{{-- Shared body for the HTTP error pages, so they match the app instead of Laravel's default look. --}}
<x-layouts.guest :title="$title">
    <p class="text-sm font-semibold tracking-widest text-subtle">ERROR {{ $code }}</p>
    <h1 class="mt-2 text-3xl font-bold">{{ $title }}</h1>
    <p class="mt-3 text-sm leading-relaxed text-muted">{{ $message }}</p>

    <div class="mt-8 flex flex-wrap gap-3">
        {{-- "/" redirects to the dashboard or the login page; a 404 renders without the session, so auth() is unknown here. --}}
        <a href="{{ url('/') }}" class="btn-primary">Ke beranda</a>
        <button type="button" onclick="history.back()" class="btn-secondary">Kembali</button>
    </div>
</x-layouts.guest>
