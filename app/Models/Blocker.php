<?php

namespace App\Models;

use App\Enums\BlockerStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blocker extends Model
{
    use HasFactory, Auditable, SoftDeletes;

    protected $fillable = [
        'feature_id',
        'team_member_id',
        'description',
        'status',
        'resolved_at',
        'resolved_by',
        'resolution_note',
    ];

    protected $casts = [
        'status'      => BlockerStatus::class,
        'resolved_at' => 'datetime',
    ];

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function resolvedByMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'resolved_by');
    }
}
