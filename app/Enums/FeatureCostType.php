<?php

namespace App\Enums;

enum FeatureCostType: string
{
    case OneTime   = 'one_time';
    case Recurring = 'recurring';

    public function label(): string
    {
        return match($this) {
            self::OneTime   => 'One-Time',
            self::Recurring => 'Recurring',
        };
    }
}
