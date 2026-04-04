<?php

namespace App\Enums;

enum SpecChangeType: string
{
    case MinorClarification = 'minor_clarification';
    case ScopeChange        = 'scope_change';

    public function label(): string
    {
        return match($this) {
            self::MinorClarification => 'Minor Clarification',
            self::ScopeChange        => 'Scope Change',
        };
    }
}
