<?php

namespace App\Models;

use App\Enums\SpecChangeType;
use App\Enums\SpecVersionState;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeatureSpecVersion extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'feature_spec_versions';

    protected $fillable = [
        'feature_id',
        'version_number',
        'content',
        'author_id',
        'change_summary',
        'state',
        'acknowledged_by',
        'acknowledged_at',
        'change_type',
    ];

    protected function casts(): array
    {
        return [
            'state'            => SpecVersionState::class,
            'change_type'      => SpecChangeType::class,
            'acknowledged_at'  => 'datetime',
            'version_number'   => 'integer',
        ];
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'author_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'acknowledged_by');
    }
}
