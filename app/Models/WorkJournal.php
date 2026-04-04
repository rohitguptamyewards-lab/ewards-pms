<?php

namespace App\Models;

use App\Enums\JournalMood;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class WorkJournal extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'work_journals';

    protected $fillable = [
        'team_member_id',
        'entry_date',
        'accomplishments',
        'blockers',
        'plan_for_tomorrow',
        'reflections',
        'mood',
        'tags',
        'is_private',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'entry_date'  => 'date',
            'mood'        => JournalMood::class,
            'tags'        => 'array',
            'is_private'  => 'boolean',
        ];
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }
}
