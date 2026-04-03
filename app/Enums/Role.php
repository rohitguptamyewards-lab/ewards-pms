<?php

namespace App\Enums;

enum Role: string
{
    case CTO = 'cto';
    case CEO = 'ceo';
    case Manager = 'manager';
    case MCTeam = 'mc_team';
    case Sales = 'sales';
    case Developer = 'developer';
    case Tester = 'tester';
    case Analyst = 'analyst';

    /**
     * Roles that see the manager/leadership dashboard.
     */
    public static function managerRoles(): array
    {
        return ['cto', 'ceo', 'manager', 'mc_team'];
    }

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match($this) {
            Role::CTO       => 'CTO',
            Role::CEO       => 'CEO',
            Role::Manager   => 'Manager',
            Role::MCTeam    => 'MC Team',
            Role::Sales     => 'Sales',
            Role::Developer => 'Developer',
            Role::Tester    => 'Tester',
            Role::Analyst   => 'Analyst',
        };
    }
}
