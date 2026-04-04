<?php

namespace App\Models;

use App\Enums\LeaveSource;
use App\Enums\LeaveType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveEntry extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'leave_entries';

    protected $fillable = [
        'team_member_id',
        'leave_date',
        'leave_type',
        'half_day',
        'source',
        'hrms_reference',
    ];

    protected function casts(): array
    {
        return [
            'leave_date'  => 'date',
            'leave_type'  => LeaveType::class,
            'source'      => LeaveSource::class,
            'half_day'    => 'boolean',
        ];
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id');
    }
}
