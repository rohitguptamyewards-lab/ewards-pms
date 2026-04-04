<?php

namespace App\Enums;

enum AssignmentState: string
{
    case Assigned   = 'assigned';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Removed    = 'removed';

    public function label(): string
    {
        return match($this) {
            self::Assigned   => 'Assigned',
            self::InProgress => 'In Progress',
            self::Completed  => 'Completed',
            self::Removed    => 'Removed',
        };
    }
}
