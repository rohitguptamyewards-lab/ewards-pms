<?php

namespace App\Models;

use App\Enums\BugOrigin;
use App\Enums\BugRootCause;
use App\Enums\BugSeverity;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BugSlaRecord extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'bug_sla_records';

    protected $fillable = [
        'feature_id',
        'severity',
        'sla_deadline',
        'breached_at',
        'reopen_count',
        'root_cause',
        'origin',
        'attributed_dev_cost',
        'origin_feature_id',
    ];

    protected function casts(): array
    {
        return [
            'severity'     => BugSeverity::class,
            'root_cause'   => BugRootCause::class,
            'origin'       => BugOrigin::class,
            'sla_deadline' => 'datetime',
            'breached_at'  => 'datetime',
            'reopen_count'        => 'integer',
            'attributed_dev_cost' => 'decimal:2',
        ];
    }

    public function originFeature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'origin_feature_id');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
