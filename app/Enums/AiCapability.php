<?php

namespace App\Enums;

enum AiCapability: string
{
    case Code         = 'code';
    case Debug        = 'debug';
    case Architecture = 'architecture';
    case Content      = 'content';
    case Research     = 'research';

    public function label(): string
    {
        return match($this) {
            self::Code         => 'Code',
            self::Debug        => 'Debug',
            self::Architecture => 'Architecture',
            self::Content      => 'Content',
            self::Research     => 'Research',
        };
    }
}
