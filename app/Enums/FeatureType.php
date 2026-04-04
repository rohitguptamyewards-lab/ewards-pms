<?php

namespace App\Enums;

enum FeatureType: string
{
    case NEW_FEATURE = 'new_feature';
    case BUG_FIX     = 'bug_fix';
    case IMPROVEMENT  = 'improvement';
    case TECH_DEBT    = 'tech_debt';
    case RESEARCH     = 'research';

    public function label(): string
    {
        return match ($this) {
            self::NEW_FEATURE => 'New Feature',
            self::BUG_FIX     => 'Bug Fix',
            self::IMPROVEMENT  => 'Improvement',
            self::TECH_DEBT    => 'Tech Debt',
            self::RESEARCH     => 'Research',
        };
    }
}
