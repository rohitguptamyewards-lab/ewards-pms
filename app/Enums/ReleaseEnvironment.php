<?php

namespace App\Enums;

enum ReleaseEnvironment: string
{
    case Staging    = 'staging';
    case Production = 'production';

    public function label(): string
    {
        return match($this) {
            self::Staging    => 'Staging',
            self::Production => 'Production',
        };
    }
}
