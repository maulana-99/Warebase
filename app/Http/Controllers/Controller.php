<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    /**
     * Records an action in the activity log (see ActivityLogger).
     *
     * @param  array<string, mixed>  $properties
     */
    protected function audit(string $action, string $description, ?Model $subject = null, array $properties = []): void
    {
        app(ActivityLogger::class)->log($action, $description, $subject, $properties);
    }

    /**
     * Changed columns since $before, for the "properties" of an update entry.
     *
     * @param  array<string, mixed>  $before
     * @return array<string, mixed>
     */
    protected function changes(Model $model, array $before): array
    {
        return ['changes' => app(ActivityLogger::class)->changes($model, $before)];
    }
}
