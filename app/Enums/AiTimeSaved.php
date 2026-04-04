<?php

namespace App\Enums;

enum AiTimeSaved: string
{
    case Same         = 'same';
    case ThirtyMin    = '30min';
    case OneToTwoHour = '1_2h';
    case HalfDayPlus  = 'half_day_plus';
    case CostMoreTime = 'cost_more_time';

    public function label(): string
    {
        return match($this) {
            self::Same         => 'Same time',
            self::ThirtyMin    => '~30 minutes saved',
            self::OneToTwoHour => '1–2 hours saved',
            self::HalfDayPlus  => 'Half day+ saved',
            self::CostMoreTime => 'Cost more time',
        };
    }
}
