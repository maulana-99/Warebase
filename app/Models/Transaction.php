<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Header of a stock movement. Status and approval fields are set only by StockService.
 */
#[Fillable(['type', 'transaction_date', 'supplier_id', 'department_id', 'recipient_name', 'note'])]
class Transaction extends Model
{
    use HasFactory;

    /**
     * Stock history is permanent: corrections are new transactions, never deletions.
     */
    protected static function booted(): void
    {
        static::deleting(function () {
            throw new LogicException('Transactions are permanent and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'transaction_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Detail page this user may open, or null. Mirrors the access rules of the three detail routes.
     */
    public function detailUrlFor(User $user): ?string
    {
        return match ($this->type) {
            TransactionType::In => route('goods-in.show', $this),
            TransactionType::Out => $user->isAdmin() || (int) $this->created_by === $user->id ? route('requests.show', $this) : null,
            TransactionType::Adjustment => $user->isAdmin() ? route('adjustments.show', $this) : null,
        };
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::Pending;
    }

    /**
     * @return HasMany<TransactionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
