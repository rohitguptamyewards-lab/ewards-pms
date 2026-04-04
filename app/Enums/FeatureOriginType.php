<?php

namespace App\Enums;

enum FeatureOriginType: string
{
    case Request    = 'request';
    case Initiative = 'initiative';
    case TechDebt   = 'tech_debt';
    case Bug        = 'bug';
    case Idea       = 'idea';

    public function label(): string
    {
        return match ($this) {
            self::Request    => 'Request',
            self::Initiative => 'Initiative',
            self::TechDebt   => 'Tech Debt',
            self::Bug        => 'Bug',
            self::Idea       => 'Idea',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Request    => 'bg-blue-100 text-blue-700',
            self::Initiative => 'bg-purple-100 text-purple-700',
            self::TechDebt   => 'bg-orange-100 text-orange-700',
            self::Bug        => 'bg-red-100 text-red-600',
            self::Idea       => 'bg-teal-100 text-teal-700',
        };
    }
}
