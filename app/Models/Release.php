<?php

namespace App\Models;

use App\Enums\ReleaseEnvironment;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Release extends Model
{
    use HasFactory, Auditable, SoftDeletes;

    protected $fillable = [
        'version',
        'release_date',
        'environment',
        'deployed_by',
        'notes',
    ];

    protected $casts = [
        'environment'  => ReleaseEnvironment::class,
        'release_date' => 'date',
    ];

    public function deployedByMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'deployed_by');
    }

    public function releaseFeatures(): HasMany
    {
        return $this->hasMany(ReleaseFeature::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'release_features');
    }
}
