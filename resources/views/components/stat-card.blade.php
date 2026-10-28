@props(['label', 'value', 'hint' => null, 'href' => null, 'highlight' => false, 'change' => null, 'tone' => null])

<div @class([
    'relative flex min-h-40 flex-col rounded-3xl p-5',
    'bg-ink text-shell' => $highlight,
    'bg-panel' => ! $highlight,
])>
    <div class="flex items-start justify-between gap-3">
        <p @class(['text-sm font-medium', 'text-shell/70' => $highlight, 'text-muted' => ! $highlight])>{{ $label }}</p>
        @if ($href)
            <a href="{{ $href }}" @class([
                'flex size-10 shrink-0 items-center justify-center rounded-full transition',
                'bg-brand text-white hover:bg-brand/80' => $highlight,
                'bg-raised text-ink hover:bg-line' => ! $highlight,
            ]) aria-label="Lihat detail {{ $label }}">
                <x-icon name="arrow-up-right" class="size-4" />
            </a>
        @endif
    </div>

    <div class="mt-auto flex items-center gap-3 pt-6">
        <p class="text-4xl font-semibold tracking-tight">{{ $value }}</p>
        @if ($tone)
            <span @class(['badge', 'bg-rose/90 text-on-accent' => $tone === 'danger', 'bg-mint text-on-accent' => $tone === 'success', 'bg-sun text-on-accent' => $tone === 'warning'])>
                {{ $slot }}
            </span>
        @elseif ($change !== null)
            <span @class(['badge text-on-accent', 'bg-mint' => $change >= 0, 'bg-rose' => $change < 0])>
                <x-icon :name="$change >= 0 ? 'arrow-up' : 'arrow-down'" class="size-3" />
                {{ number_format(abs($change), 1, ',', '.') }}%
            </span>
        @endif
    </div>

    @if ($hint)
        <p @class(['mt-2 text-xs', 'text-shell/60' => $highlight, 'text-subtle' => ! $highlight])>{{ $hint }}</p>
    @endif
</div>
