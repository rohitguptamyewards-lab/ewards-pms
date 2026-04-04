<?php

namespace App\Models;

use App\Enums\GitProviderType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class GitProvider extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'git_providers';

    protected $fillable = [
        'name',
        'provider_type',
        'base_url',
        'credentials',
        'key_version',
        'is_active',
    ];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'provider_type' => GitProviderType::class,
            'is_active'     => 'boolean',
        ];
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class, 'git_provider_id');
    }

    public function setCredentialsAttribute(?string $value): void
    {
        $this->attributes['credentials'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDecryptedCredentials(): ?string
    {
        return $this->attributes['credentials'] ? Crypt::decryptString($this->attributes['credentials']) : null;
    }
}
