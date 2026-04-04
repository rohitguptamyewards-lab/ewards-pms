<?php

namespace App\Enums;

enum OnboardingStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed  = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::InProgress => 'In Progress',
            self::Completed  => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotStarted => 'bg-gray-100 text-gray-600',
            self::InProgress => 'bg-yellow-100 text-yellow-700',
            self::Completed  => 'bg-emerald-100 text-emerald-700',
        };
    }
}
