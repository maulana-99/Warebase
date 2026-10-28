@props(['status'])

<span @class([
    'badge',
    'bg-sun/15 text-warning' => $status === \App\Enums\TransactionStatus::Pending,
    'bg-mint/15 text-success' => $status === \App\Enums\TransactionStatus::Approved,
    'bg-rose/15 text-danger' => $status === \App\Enums\TransactionStatus::Rejected,
])>{{ $status->label() }}</span>
