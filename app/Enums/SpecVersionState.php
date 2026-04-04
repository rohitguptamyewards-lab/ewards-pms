<?php

namespace App\Enums;

enum SpecVersionState: string
{
    case Draft       = 'draft';
    case UnderReview = 'under_review';
    case Frozen      = 'frozen';
    case Superseded  = 'superseded';

    public function label(): string
    {
        return match($this) {
            self::Draft       => 'Draft',
            self::UnderReview => 'Under Review',
            self::Frozen      => 'Frozen',
            self::Superseded  => 'Superseded',
        };
    }
}
