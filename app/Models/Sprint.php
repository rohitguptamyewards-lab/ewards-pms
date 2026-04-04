<?php

namespace App\Models;

use App\Enums\SprintStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sprint extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'sprints';

    protected $fillable = [
        'sprint_number',
        'goal',
        'start_date',
        'end_date',
        'total_capacity_hours',
        'committed_hours',
        'status',
        'capacity_override_reason',
    ];

    protected function casts(): array
    {
        return [
            'status'     => SprintStatus::class,
            'start_date' => 'date',
            'end_date'   => 'date',
            'total_capacity_hours' => 'integer',
            'committed_hours'      => 'integer',
        ];
    }

    public function sprintFeatures(): HasMany
    {
        return $this->hasMany(SprintFeature::class, 'sprint_id');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'sprint_features', 'sprint_id', 'feature_id')
            ->withPivot(['committed_hours', 'carried_over', 'carry_over_reason'])
            ->withTimestamps();
    }
}
