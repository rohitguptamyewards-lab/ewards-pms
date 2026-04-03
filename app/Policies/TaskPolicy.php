<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TeamMember;

class TaskPolicy
{
    /**
     * The assigned user, project owner, or CTO can update a task.
     */
    public function update(TeamMember $user, Task $task): bool
    {
        $role = $user->role->value ?? $user->role;

        if ($role === 'cto') {
            return true;
        }

        if ($user->id === $task->assigned_to) {
            return true;
        }

        if ($user->id === $task->project?->owner_id) {
            return true;
        }

        return false;
    }
}
