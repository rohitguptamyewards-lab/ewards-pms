<?php

namespace App\Enums;

enum DecisionStatus: string
{
    case Proposed = 'proposed';
    case Open = 'open';
    case Decided = 'decided';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Proposed   => 'Proposed',
            self::Open       => 'Open',
            self::Decided    => 'Decided',
            self::Superseded => 'Superseded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Proposed   => 'blue-500',
            self::Open       => 'yellow-600',
            self::Decided    => 'emerald-600',
            self::Superseded => 'gray-400',
        };
    }
}
