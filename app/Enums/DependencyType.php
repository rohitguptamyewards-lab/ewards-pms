<?php

namespace App\Enums;

enum DependencyType: string
{
    case Blocks    = 'blocks';
    case DependsOn = 'depends_on';
    case RelatedTo = 'related_to';

    public function label(): string
    {
        return match($this) {
            self::Blocks    => 'Blocks',
            self::DependsOn => 'Depends On',
            self::RelatedTo => 'Related To',
        };
    }
}
