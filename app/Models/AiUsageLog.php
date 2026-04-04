<?php

namespace App\Models;

use App\Enums\AiCapability;
use App\Enums\AiContribution;
use App\Enums\AiOutcome;
use App\Enums\AiTimeSaved;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    use HasFactory;

    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'work_log_id',
        'team_member_id',
        'ai_tool_id',
        'capability',
        'contribution',
        'outcome',
        'time_saved',
        'note',
        'prompt_template_id',
    ];

    protected function casts(): array
    {
        return [
            'capability'   => AiCapability::class,
            'contribution' => AiContribution::class,
            'outcome'      => AiOutcome::class,
            'time_saved'   => AiTimeSaved::class,
        ];
    }

    public function workLog(): BelongsTo
    {
        return $this->belongsTo(\App\Models\WorkLog::class, 'work_log_id');
    }

    public function aiTool(): BelongsTo
    {
        return $this->belongsTo(AiTool::class, 'ai_tool_id');
    }

    public function promptTemplate(): BelongsTo
    {
        return $this->belongsTo(PromptTemplate::class, 'prompt_template_id');
    }
}
