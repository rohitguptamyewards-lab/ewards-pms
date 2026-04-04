<?php

namespace App\Enums;

enum DeadlineState: string
{
    case OnTrack = 'on_track';
    case AtRisk  = 'at_risk';
    case Overdue = 'overdue';
    case Met     = 'met';
    case Missed  = 'missed';

    public function label(): string
    {
        return match($this) {
            self::OnTrack => 'On Track',
            self::AtRisk  => 'At Risk',
            self::Overdue => 'Overdue',
            self::Met     => 'Met',
            self::Missed  => 'Missed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::OnTrack => 'bg-emerald-100 text-emerald-700',
            self::AtRisk  => 'bg-yellow-100 text-yellow-700',
            self::Overdue => 'bg-red-100 text-red-700',
            self::Met     => 'bg-blue-100 text-blue-700',
            self::Missed  => 'bg-gray-100 text-gray-600',
        };
    }
}
