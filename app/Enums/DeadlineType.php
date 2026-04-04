<?php

namespace App\Enums;

enum DeadlineType: string
{
    case Hard      = 'hard';
    case Target    = 'target';
    case Soft      = 'soft';
    case Recurring = 'recurring';

    public function label(): string
    {
        return match($this) {
            self::Hard      => 'Hard Deadline',
            self::Target    => 'Target Deadline',
            self::Soft      => 'Soft Deadline',
            self::Recurring => 'Recurring Deadline',
        };
    }
}
