<?php

namespace App\Enums;

enum IdeaStatus: string
{
    case New = 'new';
    case UnderReview = 'under_review';
    case Promoted = 'promoted';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::New         => 'New',
            self::UnderReview => 'Under Review',
            self::Promoted    => 'Promoted',
            self::Archived    => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New         => 'blue-600',
            self::UnderReview => 'yellow-600',
            self::Promoted    => 'emerald-600',
            self::Archived    => 'gray-400',
        };
    }
}
