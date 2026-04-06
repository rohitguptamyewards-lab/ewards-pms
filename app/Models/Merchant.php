<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'merchants';

    protected $fillable = [
        'name',
        'type',
        'tier',
        'industry',
        'account_manager_id',
        'contract_value',
        'contract_start',
        'contract_end',
        'contact_email',
        'contact_phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'contract_value' => 'decimal:2',
            'contract_start' => 'date',
            'contract_end'   => 'date',
        ];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(PmsRequest::class, 'merchant_id');
    }
}
