<?php

namespace App\Models;

use App\Enums\ChangelogStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Changelog extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'changelogs';

    protected $fillable = [
        'title',
        'body',
        'status',
        'drafted_by',
        'approved_by',
        'published_at',
        'audience_module_ids',
    ];

    protected function casts(): array
    {
        return [
            'status'              => ChangelogStatus::class,
            'published_at'        => 'datetime',
            'audience_module_ids' => 'array',
        ];
    }

    public function drafter(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'drafted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'approved_by');
    }
}
