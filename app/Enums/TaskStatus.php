<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Done = 'done';

    /**
     * Get the valid next statuses from this status.
     *
     * @return array<TaskStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::InProgress],
            self::InProgress => [self::Blocked, self::Done],
            self::Blocked => [self::InProgress],
            self::Done => [],
        };
    }
}
