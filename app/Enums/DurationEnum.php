<?php

namespace App\Enums;

enum DurationEnum: string
{
    case ThirtyMin  = '30m';
    case OneHour    = '1h';
    case TwoHours   = '2h';
    case ThreeHours = '3h';
    case HalfDay    = 'half_day';
    case FullDay    = 'full_day';

    public function label(): string
    {
        return match($this) {
            self::ThirtyMin  => '30 minutes',
            self::OneHour    => '1 hour',
            self::TwoHours   => '2 hours',
            self::ThreeHours => '3 hours',
            self::HalfDay    => 'Half day',
            self::FullDay    => 'Full day',
        };
    }

    public function hours(): float
    {
        return match($this) {
            self::ThirtyMin  => 0.5,
            self::OneHour    => 1.0,
            self::TwoHours   => 2.0,
            self::ThreeHours => 3.0,
            self::HalfDay    => 4.0,
            self::FullDay    => 8.0,
        };
    }
}
