@props(['status'])

<span @class([
    'badge',
    'bg-sun/15 text-sun' => $status === \App\Enums\TransactionStatus::Pending,
    'bg-mint/15 text-mint' => $status === \App\Enums\TransactionStatus::Approved,
    'bg-rose/15 text-rose' => $status === \App\Enums\TransactionStatus::Rejected,
])>{{ $status->label() }}</span>
