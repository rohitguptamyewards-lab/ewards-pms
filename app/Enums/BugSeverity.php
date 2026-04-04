<?php

namespace App\Enums;

enum BugSeverity: string
{
    case P0 = 'p0';
    case P1 = 'p1';
    case P2 = 'p2';
    case P3 = 'p3';

    public function label(): string
    {
        return match($this) {
            self::P0 => 'P0 — Critical',
            self::P1 => 'P1 — High',
            self::P2 => 'P2 — Medium',
            self::P3 => 'P3 — Low',
        };
    }

    public function slaHours(): ?int
    {
        return match($this) {
            self::P0 => 4,
            self::P1 => 24,
            self::P2 => 72,
            self::P3 => null, // next sprint
        };
    }
}
