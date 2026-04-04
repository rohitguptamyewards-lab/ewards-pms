<?php

namespace App\Enums;

enum CarryOverReason: string
{
    case Blocked        = 'blocked';
    case Underestimated = 'underestimated';
    case ScopeCrept     = 'scope_crept';
    case Deprioritised  = 'deprioritised';

    public function label(): string
    {
        return match($this) {
            self::Blocked        => 'Blocked',
            self::Underestimated => 'Underestimated',
            self::ScopeCrept     => 'Scope Crept',
            self::Deprioritised  => 'Deprioritised',
        };
    }
}
