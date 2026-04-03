<?php

namespace App\Models;

use App\Enums\Role;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class TeamMember extends Authenticatable
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'team_members';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'joining_date',
        'weekly_capacity',
        'working_hours',
        'timezone',
        'is_active',
        'contractor_flag',
        'freelancer_flag',
        'git_username',
        'notification_preferences',
        'cost_rate_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'ctc',
        'hourly_rate',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'notification_preferences' => 'array',
            'is_active' => 'boolean',
            'contractor_flag' => 'boolean',
            'freelancer_flag' => 'boolean',
            'password' => 'hashed',
            'joining_date' => 'date',
            'weekly_capacity' => 'decimal:2',
            'working_hours' => 'decimal:2',
        ];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members', 'user_id', 'project_id');
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class, 'user_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(PmsRequest::class, 'requested_by');
    }
}
