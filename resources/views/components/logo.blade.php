{{-- Warebase mark: a crate with a "W" cut-out and amber tape. The crate follows the text color, so it works in both themes. --}}
@php $mask = 'logo-mask-'.\Illuminate\Support\Str::random(6); @endphp
<svg {{ $attributes->merge(['class' => 'size-9']) }} viewBox="0 0 52 44" fill="none" aria-hidden="true">
    <g transform="translate(-6 -12)">
        <mask id="{{ $mask }}" maskUnits="userSpaceOnUse" x="0" y="0" width="64" height="64">
            <rect x="6" y="12" width="52" height="44" rx="5" fill="#fff" />
            <line x1="6" y1="23" x2="58" y2="23" stroke="#000" stroke-width="2.5" />
            <polyline points="18,33 24.5,45 32,36 39.5,45 46,33" fill="none" stroke="#000" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
        </mask>
        <rect x="6" y="12" width="52" height="44" rx="5" fill="currentColor" mask="url(#{{ $mask }})" />
        <rect x="28" y="12" width="8" height="9.75" fill="#F5A524" />
    </g>
</svg>
