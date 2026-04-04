<?php

namespace App\Models;

use App\Enums\DependencyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureDependency extends Model
{
    protected $table = 'feature_dependencies';

    protected $fillable = [
        'parent_feature_id',
        'child_feature_id',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => DependencyType::class,
        ];
    }

    public function parentFeature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'parent_feature_id');
    }

    public function childFeature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'child_feature_id');
    }
}
