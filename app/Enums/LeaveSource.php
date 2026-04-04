<?php

namespace App\Enums;

enum LeaveSource: string
{
    case Manual = 'manual';
    case HRMS   = 'hrms';

    public function label(): string
    {
        return match($this) {
            self::Manual => 'Manual',
            self::HRMS   => 'HRMS',
        };
    }
}
