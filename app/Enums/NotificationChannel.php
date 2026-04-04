<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case Slack = 'slack';

    public function label(): string
    {
        return match($this) {
            self::InApp => 'In-App',
            self::Email => 'Email',
            self::Slack => 'Slack',
        };
    }
}
