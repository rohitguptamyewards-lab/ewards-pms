<?php

namespace App\Models;

use App\Enums\IdeaStatus;
use App\Models\Comment;
use App\Models\Document;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Idea extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'ideas';

    protected $fillable = [
        'title',
        'description',
        'source',
        'created_by',
        'status',
        'promoted_to_type',
        'promoted_to_id',
        'last_activity_at',
        'review_reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'status'             => IdeaStatus::class,
            'last_activity_at'   => 'datetime',
            'review_reminded_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'created_by');
    }

    public function promotedTo(): MorphTo
    {
        return $this->morphTo('promoted_to');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
