<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureUsageSnapshot extends Model
{
    use HasFactory;

    protected $table = 'feature_usage_snapshots';

    protected $fillable = [
        'feature_id',
        'date',
        'merchants_using_count',
        'first_used_at',
        'last_used_at',
        'total_usage_count',
        'revenue_attributed',
        'abandoned_count',
    ];

    protected function casts(): array
    {
        return [
            'date'                  => 'date',
            'first_used_at'         => 'datetime',
            'last_used_at'          => 'datetime',
            'merchants_using_count' => 'integer',
            'total_usage_count'     => 'integer',
            'revenue_attributed'    => 'decimal:2',
            'abandoned_count'       => 'integer',
        ];
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
