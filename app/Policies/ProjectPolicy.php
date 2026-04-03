<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\TeamMember;

class ProjectPolicy
{
    /**
     * Any authenticated user can view projects.
     */
    public function viewAny(TeamMember $user): bool
    {
        return true;
    }

    /**
     * Only CTO can create projects.
     */
    public function create(TeamMember $user): bool
    {
        return ($user->role->value ?? $user->role) === 'cto';
    }

    /**
     * Project owner or CTO can update.
     */
    public function update(TeamMember $user, Project $project): bool
    {
        $role = $user->role->value ?? $user->role;

        return $user->id === $project->owner_id || $role === 'cto';
    }

    /**
     * Only CTO can delete projects.
     */
    public function delete(TeamMember $user, Project $project): bool
    {
        return ($user->role->value ?? $user->role) === 'cto';
    }
}
