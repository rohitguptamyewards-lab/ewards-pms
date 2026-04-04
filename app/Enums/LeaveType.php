<?php

namespace App\Enums;

enum LeaveType: string
{
    case Planned = 'planned';
    case Sick    = 'sick';
    case Holiday = 'holiday';
    case Other   = 'other';

    public function label(): string
    {
        return match($this) {
            self::Planned => 'Planned',
            self::Sick    => 'Sick',
            self::Holiday => 'Holiday',
            self::Other   => 'Other',
        };
    }
}
