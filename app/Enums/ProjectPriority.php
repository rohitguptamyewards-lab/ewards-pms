<?php

namespace App\Enums;

enum ProjectPriority: string
{
    case Low      = 'low';
    case Medium   = 'medium';
    case High     = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low      => 'Low',
            self::Medium   => 'Medium',
            self::High     => 'High',
            self::Critical => 'Critical',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low      => 'gray-400',
            self::Medium   => 'yellow-500',
            self::High     => 'orange-500',
            self::Critical => 'red-600',
        };
    }
}
