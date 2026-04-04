<?php

namespace App\Models;

use App\Enums\CarryOverReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprintFeature extends Model
{
    protected $table = 'sprint_features';

    protected $fillable = [
        'sprint_id',
        'feature_id',
        'committed_hours',
        'carried_over',
        'carry_over_reason',
    ];

    protected function casts(): array
    {
        return [
            'carried_over'      => 'boolean',
            'carry_over_reason' => CarryOverReason::class,
            'committed_hours'   => 'integer',
        ];
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class, 'sprint_id');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
