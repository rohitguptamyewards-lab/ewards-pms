<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::insertAuditLog($model, 'created', $model->getAttributes());
        });

        static::updated(function ($model) {
            static::insertAuditLog($model, 'updated', $model->getChanges());
        });

        static::deleted(function ($model) {
            static::insertAuditLog($model, 'deleted', null);
        });
    }

    protected static function insertAuditLog($model, string $action, ?array $changes): void
    {
        try {
            DB::table('audit_logs')->insert([
                'entity_type' => get_class($model),
                'entity_id' => $model->id,
                'action' => $action,
                'user_id' => auth()->id(),
                'changes' => $changes !== null ? json_encode($changes) : null,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silently fail to prevent audit logging from breaking
            // the primary operation. Log the error instead.
            logger()->error('Audit log insert failed', [
                'entity_type' => get_class($model),
                'entity_id' => $model->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
