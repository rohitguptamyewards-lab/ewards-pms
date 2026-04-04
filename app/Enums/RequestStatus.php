<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Received             = 'received';
    case UnderReview          = 'under_review';
    case ClarificationNeeded = 'clarification_needed';
    case Linked               = 'linked';
    case Deferred             = 'deferred';
    case Rejected             = 'rejected';
    case Fulfilled            = 'fulfilled';

    public function label(): string
    {
        return match ($this) {
            self::Received             => 'Received',
            self::UnderReview          => 'Under Review',
            self::ClarificationNeeded => 'Clarification Needed',
            self::Linked               => 'Linked',
            self::Deferred             => 'Deferred',
            self::Rejected             => 'Rejected',
            self::Fulfilled            => 'Fulfilled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Received             => 'bg-slate-100 text-slate-700',
            self::UnderReview          => 'bg-yellow-100 text-yellow-700',
            self::ClarificationNeeded => 'bg-amber-100 text-amber-700',
            self::Linked               => 'bg-blue-100 text-blue-700',
            self::Deferred             => 'bg-orange-100 text-orange-700',
            self::Rejected             => 'bg-red-100 text-red-700',
            self::Fulfilled            => 'bg-emerald-100 text-emerald-700',
        };
    }
}
