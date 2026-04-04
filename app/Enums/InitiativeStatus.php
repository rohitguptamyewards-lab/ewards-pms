<?php

namespace App\Enums;

enum InitiativeStatus: string
{
    case Planning = 'planning';
    case Active = 'active';
    case AtRisk = 'at_risk';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Planning  => 'Planning',
            self::Active    => 'Active',
            self::AtRisk    => 'At Risk',
            self::Completed => 'Completed',
            self::Archived  => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planning  => 'gray-500',
            self::Active    => 'emerald-600',
            self::AtRisk    => 'orange-500',
            self::Completed => 'teal-600',
            self::Archived  => 'gray-400',
        };
    }
}
