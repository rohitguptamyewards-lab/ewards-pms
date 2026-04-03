<?php

namespace App\Models;

use App\Enums\FeatureStatus;
use App\Enums\TaskPriority;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'deadline',
        'estimated_hours',
        'assigned_to',
        'qa_owner_id',
        'spec_version',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => FeatureStatus::class,
            'priority' => TaskPriority::class,
            'deadline' => 'date',
            'estimated_hours' => 'decimal:2',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'assigned_to');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(PmsRequest::class, 'linked_feature_id');
    }
}
