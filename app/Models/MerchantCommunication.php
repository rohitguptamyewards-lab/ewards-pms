<?php

namespace App\Models;

use App\Enums\CommunicationChannel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantCommunication extends Model
{
    use HasFactory, Auditable, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'team_member_id',
        'feature_id',
        'channel',
        'summary',
        'commitment_made',
        'commitment_date',
    ];

    protected $casts = [
        'channel'         => CommunicationChannel::class,
        'commitment_made' => 'boolean',
        'commitment_date' => 'date',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
