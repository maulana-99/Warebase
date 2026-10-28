@props(['type'])

{{-- Transaction type with a fixed color: in = green, out = orange, adjustment = blue. --}}
<span {{ $attributes->class([
    'badge gap-1',
    'bg-mint/15 text-success' => $type === \App\Enums\TransactionType::In,
    'bg-peach/20 text-warning' => $type === \App\Enums\TransactionType::Out,
    'bg-sky/15 text-info' => $type === \App\Enums\TransactionType::Adjustment,
]) }}>
    <x-icon :name="match ($type) { \App\Enums\TransactionType::In => 'arrow-down', \App\Enums\TransactionType::Out => 'arrow-up', default => 'scale' }" class="size-3" />
    {{ $type->label() }}
</span>
