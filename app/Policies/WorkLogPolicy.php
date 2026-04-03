<?php

namespace App\Policies;

use App\Models\TeamMember;
use App\Models\WorkLog;

class WorkLogPolicy
{
    /**
     * Own logs only or CTO can update.
     */
    public function update(TeamMember $user, WorkLog $workLog): bool
    {
        $role = $user->role->value ?? $user->role;

        return $user->id === $workLog->user_id || $role === 'cto';
    }

    /**
     * Own logs only or CTO can delete.
     */
    public function delete(TeamMember $user, WorkLog $workLog): bool
    {
        $role = $user->role->value ?? $user->role;

        return $user->id === $workLog->user_id || $role === 'cto';
    }
}
