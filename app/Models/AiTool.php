<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiTool extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'ai_tools';

    protected $fillable = [
        'name',
        'provider',
        'capabilities',
        'cost_per_seat_monthly',
        'seats_purchased',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capabilities'          => 'array',
            'cost_per_seat_monthly' => 'decimal:2',
            'seats_purchased'       => 'integer',
            'is_active'             => 'boolean',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AiToolAssignment::class, 'ai_tool_id');
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class, 'ai_tool_id');
    }

    public function promptTemplates(): HasMany
    {
        return $this->hasMany(PromptTemplate::class, 'ai_tool_id');
    }
}
