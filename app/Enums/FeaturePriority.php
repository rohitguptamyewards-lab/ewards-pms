<?php

namespace App\Enums;

enum FeaturePriority: string
{
    case P0 = 'p0';
    case P1 = 'p1';
    case P2 = 'p2';
    case P3 = 'p3';

    public function label(): string
    {
        return match ($this) {
            self::P0 => 'P0 (Critical)',
            self::P1 => 'P1 (High)',
            self::P2 => 'P2 (Medium)',
            self::P3 => 'P3 (Low)',
        };
    }

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
