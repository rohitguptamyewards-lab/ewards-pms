<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureCostSnapshot extends Model
{
    use HasFactory;

    protected $table = 'feature_cost_snapshots';

    protected $fillable = [
        'feature_id',
        'total_cost',
        'cost_by_person',
        'cost_by_activity_type',
        'estimated_hours',
        'actual_hours',
        'snapshot_date',
    ];

    protected function casts(): array
    {
        return [
            'total_cost'             => 'decimal:2',
            'cost_by_person'         => 'array',
            'cost_by_activity_type'  => 'array',
            'estimated_hours'        => 'integer',
            'actual_hours'           => 'integer',
            'snapshot_date'          => 'date',
        ];
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
