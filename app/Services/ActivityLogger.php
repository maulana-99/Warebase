<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes the audit trail: who did what, to which record, from which IP. Rows are never changed or deleted.
 *
 * Logging runs after the action itself has succeeded, so it must never turn that success into an error:
 * a failed write is reported to the error log instead of thrown.
 */
class ActivityLogger
{
    /** Columns never written to the log, even as "changed". */
    private const HIDDEN = ['password', 'remember_token', 'updated_at', 'created_at'];

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, string $description, ?Model $subject = null, array $properties = [], ?User $user = null): void
    {
        try {
            ActivityLog::create([
                'user_id' => ($user ?? Auth::user())?->getKey(),
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                // Descriptions include user-entered names; keep them within the 255-character column.
                'description' => Str::limit($description, 252),
                'properties' => $properties ?: null,
                'ip_address' => request()->ip(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Old and new values of the columns that changed in the last save.
     *
     * @param  array<string, mixed>  $before  the model's attributes before the change
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function changes(Model $model, array $before): array
    {
        $changes = [];
        foreach ($model->getChanges() as $column => $new) {
            if (in_array($column, self::HIDDEN, true)) {
                continue;
            }
            $changes[$column] = ['old' => $before[$column] ?? null, 'new' => $new];
        }
        if ($model->wasChanged('password')) {
            $changes['password'] = ['old' => '***', 'new' => '***'];
        }

        return $changes;
    }
}
