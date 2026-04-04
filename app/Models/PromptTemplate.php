<?php

namespace App\Models;

use App\Enums\AiCapability;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromptTemplate extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'prompt_templates';

    protected $fillable = [
        'ai_tool_id',
        'capability',
        'title',
        'content',
        'tags',
        'created_by',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'capability'  => AiCapability::class,
            'tags'        => 'array',
            'usage_count' => 'integer',
        ];
    }

    public function aiTool(): BelongsTo
    {
        return $this->belongsTo(AiTool::class, 'ai_tool_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'created_by');
    }
}
