<?php

namespace App\Models;

use App\Enums\DeadlineState;
use App\Enums\DeadlineType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deadline extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'deadlines';

    protected $fillable = [
        'deadlineable_type',
        'deadlineable_id',
        'type',
        'state',
        'due_date',
        'reminder_sent_7d',
        'reminder_sent_3d',
        'reminder_sent_1d',
        'cascade_from_id',
    ];

    protected function casts(): array
    {
        return [
            'type'             => DeadlineType::class,
            'state'            => DeadlineState::class,
            'due_date'         => 'date',
            'reminder_sent_7d' => 'boolean',
            'reminder_sent_3d' => 'boolean',
            'reminder_sent_1d' => 'boolean',
        ];
    }

    public function deadlineable(): MorphTo
    {
        return $this->morphTo();
    }

    public function cascadeFrom(): BelongsTo
    {
        return $this->belongsTo(Deadline::class, 'cascade_from_id');
    }
}
