<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\AiCapability;
use App\Enums\AiContribution;
use App\Enums\AiOutcome;
use App\Enums\AiTimeSaved;
use App\Enums\DurationEnum;
use App\Enums\EffortConfidence;
use App\Models\AiTool;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'activity_logs';

    protected $fillable = [
        'team_member_id',
        'activity_type',
        'feature_id',
        'duration',
        'effort_confidence',
        'status',
        'blocker_reason',
        'note',
        'ai_used',
        'ai_tool_id',
        'ai_capability',
        'ai_contribution',
        'ai_outcome',
        'ai_time_saved',
        'ai_note',
        'cost_rate_id',
        'log_date',
        'is_same_as_yesterday',
    ];

    protected function casts(): array
    {
        return [
            'duration'             => DurationEnum::class,
            'effort_confidence'    => EffortConfidence::class,
            'status'               => ActivityStatus::class,
            'ai_capability'        => AiCapability::class,
            'ai_contribution'      => AiContribution::class,
            'ai_outcome'           => AiOutcome::class,
            'ai_time_saved'        => AiTimeSaved::class,
            'ai_used'              => 'boolean',
            'is_same_as_yesterday' => 'boolean',
            'log_date'             => 'date',
        ];
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }

    public function aiTool(): BelongsTo
    {
        return $this->belongsTo(AiTool::class, 'ai_tool_id');
    }
}
