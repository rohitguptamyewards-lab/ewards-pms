<?php

namespace App\Models;

use App\Enums\WorkLogStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class WorkLog extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'work_logs';

    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'log_date',
        'hours_spent',
        'status',
        'note',
        'blocker',
    ];

    protected function casts(): array
    {
        return [
            'log_date'    => 'date',
            'hours_spent' => 'decimal:2',
            'status'      => WorkLogStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
