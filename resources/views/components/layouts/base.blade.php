@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

        {{-- Apply the saved theme (or the OS preference) before paint, so the page never flashes. --}}
        <script>
            (() => {
                let theme = null;
                try { theme = localStorage.getItem('theme'); } catch (e) {}
                if (theme !== 'light' && theme !== 'dark') {
                    theme = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
                }
                document.documentElement.dataset.theme = theme;
            })();
        </script>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body {{ $attributes->merge(['class' => 'min-h-screen']) }}>
        {{ $slot }}
    </body>
</html>
