<?php

namespace App\Enums;

enum AiOutcome: string
{
    case Helpful    = 'helpful';
    case Partially  = 'partially';
    case Misleading = 'misleading';
    case NotUseful  = 'not_useful';

    public function label(): string
    {
        return match($this) {
            self::Helpful    => 'Helpful',
            self::Partially  => 'Partially helpful',
            self::Misleading => 'Misleading',
            self::NotUseful  => 'Not useful',
        };
    }
}
