<?php

namespace App\Concerns;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Records created/updated/deleted events. Hidden attributes (passwords, tokens) are never logged.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($model) => $model->audit('created', null, $model->auditableAttributes($model->getAttributes())));

        static::updated(function ($model) {
            $changes = $model->auditableAttributes($model->getChanges());
            unset($changes['updated_at']);

            if ($changes === []) {
                return;
            }

            $old = array_intersect_key($model->auditableAttributes($model->getOriginal()), $changes);
            $model->audit('updated', $old, $changes);
        });

        static::deleted(fn ($model) => $model->audit('deleted', $model->auditableAttributes($model->getOriginal()), null));
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function audit(string $event, ?array $old, ?array $new): void
    {
        app(AuditLogger::class)->record($event, $this, $old, $new);
    }

    public function auditableAttributes(array $attributes): array
    {
        return array_diff_key($attributes, array_flip([...$this->getHidden(), 'password', 'remember_token']));
    }
}
