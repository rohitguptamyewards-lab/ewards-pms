<?php

namespace App\Models;

use App\Enums\DecisionStatus;
use App\Models\Comment;
use App\Models\Document;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Decision extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'decisions';

    protected $fillable = [
        'title',
        'context',
        'options_considered',
        'chosen_option',
        'rationale',
        'decision_maker_id',
        'decision_date',
        'linked_to_type',
        'linked_to_id',
        'status',
        'superseded_by',
    ];

    protected function casts(): array
    {
        return [
            'status'        => DecisionStatus::class,
            'decision_date' => 'date',
        ];
    }

    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'decision_maker_id');
    }

    public function linkedTo(): MorphTo
    {
        return $this->morphTo('linked_to');
    }

    public function supersededByDecision(): BelongsTo
    {
        return $this->belongsTo(Decision::class, 'superseded_by');
    }

    public function supersedes(): HasOne
    {
        return $this->hasOne(Decision::class, 'superseded_by');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * BR-020/BR-032: Decisions are append-only. Cannot delete, only supersede.
     */
    public function delete(): ?bool
    {
        throw new \LogicException('Decisions cannot be deleted. Use supersede instead (BR-032).');
    }
}
