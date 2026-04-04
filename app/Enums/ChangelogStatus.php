<?php

namespace App\Enums;

enum ChangelogStatus: string
{
    case Draft     = 'draft';
    case Approved  = 'approved';
    case Published = 'published';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Draft',
            self::Approved  => 'Approved',
            self::Published => 'Published',
        };
    }
}
