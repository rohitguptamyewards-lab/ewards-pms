<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Enums\RequestUrgency;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PmsRequest extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'requests';

    protected $fillable = [
        'title',
        'description',
        'merchant_id',
        'type',
        'urgency',
        'requested_by',
        'demand_count',
        'expected_revenue_impact',
        'status',
        'linked_feature_id',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => RequestType::class,
            'urgency' => RequestUrgency::class,
            'status' => RequestStatus::class,
            'demand_count' => 'integer',
            'expected_revenue_impact' => 'decimal:2',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'requested_by');
    }

    public function linkedFeature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'linked_feature_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
