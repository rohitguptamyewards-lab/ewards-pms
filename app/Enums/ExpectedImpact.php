<?php

namespace App\Enums;

enum ExpectedImpact: string
{
    case Revenue = 'revenue';
    case Retention = 'retention';
    case OpsEfficiency = 'ops_efficiency';
    case PlatformStability = 'platform_stability';

    public function label(): string
    {
        return match ($this) {
            self::Revenue           => 'Revenue',
            self::Retention         => 'Retention',
            self::OpsEfficiency     => 'Ops Efficiency',
            self::PlatformStability => 'Platform Stability',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Revenue           => 'emerald-600',
            self::Retention         => 'blue-600',
            self::OpsEfficiency     => 'purple-600',
            self::PlatformStability => 'orange-500',
        };
    }
}
