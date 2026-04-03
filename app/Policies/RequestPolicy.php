<?php

namespace App\Policies;

use App\Models\PmsRequest;
use App\Models\TeamMember;

class RequestPolicy
{
    /**
     * Sales or CTO can create requests.
     */
    public function create(TeamMember $user): bool
    {
        $role = $user->role->value ?? $user->role;

        return in_array($role, ['sales', 'cto'], true);
    }

    /**
     * Only CTO can triage requests.
     */
    public function triage(TeamMember $user, PmsRequest $pmsRequest): bool
    {
        return ($user->role->value ?? $user->role) === 'cto';
    }
}
