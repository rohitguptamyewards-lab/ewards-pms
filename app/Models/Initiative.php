<?php

namespace App\Models;

use App\Enums\ExpectedImpact;
use App\Enums\InitiativeOriginType;
use App\Enums\InitiativeStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Initiative extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'initiatives';

    protected $fillable = [
        'title',
        'description',
        'origin_type',
        'business_case',
        'expected_impact',
        'owner_id',
        'deadline',
        'status',
        'estimated_features',
        'module_id',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'status'          => InitiativeStatus::class,
            'origin_type'     => InitiativeOriginType::class,
            'expected_impact' => ExpectedImpact::class,
            'deadline'        => 'date',
            'estimated_features' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'owner_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(Feature::class, 'initiative_id');
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
