<?php

namespace App\Enums;

enum CommunicationChannel: string
{
    case Call    = 'call';
    case Email   = 'email';
    case Meeting = 'meeting';

    public function label(): string
    {
        return match($this) {
            self::Call    => 'Call',
            self::Email   => 'Email',
            self::Meeting => 'Meeting',
        };
    }
}
