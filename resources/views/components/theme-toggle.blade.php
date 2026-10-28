{{-- Switches between light and dark; the choice is remembered in this browser. --}}
<button type="button" {{ $attributes->merge(['class' => 'icon-btn']) }} data-theme-toggle aria-label="Ganti ke mode terang" title="Ganti tema">
    <x-icon name="sun" class="theme-icon-sun size-5" />
    <x-icon name="moon" class="theme-icon-moon size-5" />
</button>
