<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Repository extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'repositories';

    protected $fillable = [
        'git_provider_id',
        'name',
        'url',
        'default_branch',
        'webhook_secret',
        'key_version',
        'is_active',
    ];

    protected $hidden = ['webhook_secret'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function gitProvider(): BelongsTo
    {
        return $this->belongsTo(GitProvider::class, 'git_provider_id');
    }

    public function gitEvents(): HasMany
    {
        return $this->hasMany(GitEvent::class, 'repository_id');
    }

    public function gitBranches(): HasMany
    {
        return $this->hasMany(GitBranch::class, 'repository_id');
    }

    public function setWebhookSecretAttribute(?string $value): void
    {
        $this->attributes['webhook_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDecryptedWebhookSecret(): ?string
    {
        return $this->attributes['webhook_secret'] ? Crypt::decryptString($this->attributes['webhook_secret']) : null;
    }
}
