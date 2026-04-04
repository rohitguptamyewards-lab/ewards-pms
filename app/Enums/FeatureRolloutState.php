<?php

namespace App\Enums;

enum FeatureRolloutState: string
{
    case Internal    = 'internal';
    case BetaPilot   = 'beta_pilot';
    case GradualGA   = 'gradual_ga';
    case FullGA      = 'full_ga';
    case Sunset      = 'sunset';

    public function label(): string
    {
        return match ($this) {
            self::Internal  => 'Internal',
            self::BetaPilot => 'Beta / Pilot',
            self::GradualGA => 'Gradual GA',
            self::FullGA    => 'Full GA',
            self::Sunset    => 'Sunset',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Internal  => 'bg-gray-100 text-gray-600',
            self::BetaPilot => 'bg-yellow-100 text-yellow-700',
            self::GradualGA => 'bg-blue-100 text-blue-700',
            self::FullGA    => 'bg-emerald-100 text-emerald-700',
            self::Sunset    => 'bg-red-100 text-red-600',
        };
    }
}
