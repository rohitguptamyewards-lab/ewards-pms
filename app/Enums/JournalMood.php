<?php

namespace App\Enums;

enum JournalMood: string
{
    case Great      = 'great';
    case Good       = 'good';
    case Neutral    = 'neutral';
    case Struggling = 'struggling';
    case Blocked    = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Great      => 'Great',
            self::Good       => 'Good',
            self::Neutral    => 'Neutral',
            self::Struggling => 'Struggling',
            self::Blocked    => 'Blocked',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Great      => '🟢',
            self::Good       => '🔵',
            self::Neutral    => '🟡',
            self::Struggling => '🟠',
            self::Blocked    => '🔴',
        };
    }
}
