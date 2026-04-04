<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleDebtScore extends Model
{
    use HasFactory;

    protected $table = 'module_debt_scores';

    protected $fillable = [
        'module_id',
        'week_date',
        'debt_backlog_size',
        'debt_backlog_hours',
        'debt_velocity',
        'debt_to_feature_ratio',
        'debt_age_distribution',
        'health_score',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'week_date'              => 'date',
            'debt_backlog_size'      => 'integer',
            'debt_backlog_hours'     => 'integer',
            'debt_velocity'          => 'integer',
            'debt_to_feature_ratio'  => 'decimal:4',
            'debt_age_distribution'  => 'array',
            'health_score'           => 'integer',
            'calculated_at'          => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }
}
