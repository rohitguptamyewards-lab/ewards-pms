<?php

namespace App\Enums;

enum TaskPriority: string
{
    case P0 = 'p0';
    case P1 = 'p1';
    case P2 = 'p2';
    case P3 = 'p3';

    /**
     * Get the Tailwind CSS color class for this priority.
     */
    public function color(): string
    {
        return match ($this) {
            self::P0 => 'red-600',
            self::P1 => 'orange-500',
            self::P2 => 'yellow-500',
            self::P3 => 'gray-400',
        };
    }
}
