@props(['product'])

{{-- Stock state: empty = red, at or below minimum = amber, otherwise green. --}}
@php($state = $product->stock === 0 ? 'empty' : ($product->isLowStock() ? 'low' : 'ok'))
<span {{ $attributes->class([
    'badge',
    'bg-rose/15 text-danger' => $state === 'empty',
    'bg-sun/20 text-warning' => $state === 'low',
    'bg-mint/15 text-success' => $state === 'ok',
]) }}>{{ ['empty' => 'Habis', 'low' => 'Menipis', 'ok' => 'Aman'][$state] }}</span>
