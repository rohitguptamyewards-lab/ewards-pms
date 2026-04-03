<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Prevent saving. AuditLog is append-only via DB::table().
     *
     * @throws \LogicException
     */
    public function save(array $options = []): bool
    {
        throw new \LogicException('AuditLog is append-only. Use DB::table(\'audit_logs\')->insert() instead.');
    }

    /**
     * Prevent updating.
     *
     * @throws \LogicException
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('AuditLog is append-only. Records cannot be modified.');
    }

    /**
     * Prevent deleting.
     *
     * @throws \LogicException
     */
    public function delete(): ?bool
    {
        throw new \LogicException('AuditLog is append-only. Records cannot be deleted.');
    }
}
