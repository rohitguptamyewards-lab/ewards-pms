<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class CostRate extends Model
{
    use Auditable, HasFactory;

    protected $table = 'cost_rates';

    protected $fillable = [
        'team_member_id',
        'monthly_ctc',
        'working_hours_per_month',
        'overhead_multiplier',
        'loaded_hourly_rate',
        'effective_from',
        'effective_to',
        'key_version',
        'created_by',
    ];

    protected $hidden = ['monthly_ctc', 'loaded_hourly_rate'];

    protected function casts(): array
    {
        return [
            'effective_from'         => 'date',
            'effective_to'           => 'date',
            'working_hours_per_month' => 'integer',
            'overhead_multiplier'    => 'decimal:2',
            'key_version'            => 'integer',
        ];
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'created_by');
    }

    public function setMonthlyCtcAttribute(?string $value): void
    {
        $this->attributes['monthly_ctc'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDecryptedMonthlyCtc(): ?string
    {
        return $this->attributes['monthly_ctc'] ? Crypt::decryptString($this->attributes['monthly_ctc']) : null;
    }

    public function setLoadedHourlyRateAttribute(?string $value): void
    {
        $this->attributes['loaded_hourly_rate'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDecryptedLoadedHourlyRate(): ?string
    {
        return $this->attributes['loaded_hourly_rate'] ? Crypt::decryptString($this->attributes['loaded_hourly_rate']) : null;
    }
}
