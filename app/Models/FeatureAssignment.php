<?php

namespace App\Models;

use App\Enums\AssignmentRole;
use App\Enums\AssignmentState;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeatureAssignment extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'feature_assignments';

    protected $fillable = [
        'feature_id',
        'team_member_id',
        'role',
        'state',
        'estimated_hours',
        'actual_hours',
        'assigned_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'role'            => AssignmentRole::class,
            'state'           => AssignmentState::class,
            'estimated_hours' => 'integer',
            'actual_hours'    => 'integer',
            'assigned_at'     => 'datetime',
            'completed_at'    => 'datetime',
        ];
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id');
    }
}
