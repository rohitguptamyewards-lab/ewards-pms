<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiToolAssignment extends Model
{
    use HasFactory;

    protected $table = 'ai_tool_assignments';

    protected $fillable = [
        'ai_tool_id',
        'team_member_id',
        'assigned_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'revoked_at'  => 'datetime',
        ];
    }

    public function aiTool(): BelongsTo
    {
        return $this->belongsTo(AiTool::class, 'ai_tool_id');
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id');
    }
}
