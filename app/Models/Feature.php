<?php

namespace App\Models;

use App\Enums\FeatureOriginType;
use App\Enums\FeaturePriority;
use App\Enums\FeatureRolloutState;
use App\Enums\FeatureStatus;
use App\Enums\FeatureType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'features';

    protected $fillable = [
        'title',
        'description',
        'type',
        'priority',
        'initiative_id',
        'module_id',
        'origin_type',
        'business_impact',
        'status',
        'rollout_state',
        'rollout_percentage',
        'rollout_notes',
        'rolled_back_at',
        'rolled_back_by',
        'deadline',
        'estimated_hours',
        'cto_estimated_hours',
        'cto_estimated_by',
        'assigned_to',
        'qa_owner_id',
        'spec_version',
        'tenant_id',
        'cost_type',
        'is_one_time_cost',
        'overhead_multiplier',
        'maintenance_cost_monthly',
        'attributed_revenue',
    ];

    protected function casts(): array
    {
        return [
            'status'        => FeatureStatus::class,
            'type'          => FeatureType::class,
            'priority'      => FeaturePriority::class,
            'origin_type'   => FeatureOriginType::class,
            'rollout_state' => FeatureRolloutState::class,
            'deadline'              => 'date',
            'estimated_hours'       => 'decimal:2',
            'cto_estimated_hours'   => 'decimal:2',
            'is_one_time_cost'      => 'boolean',
            'overhead_multiplier'   => 'decimal:2',
            'rollout_percentage'    => 'integer',
            'rolled_back_at'        => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'assigned_to');
    }

    public function qaOwner(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'qa_owner_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(PmsRequest::class, 'linked_feature_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
