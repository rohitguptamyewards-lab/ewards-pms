<?php

namespace App\Enums;

enum AssignmentRole: string
{
    case Developer = 'developer';
    case Tester    = 'tester';
    case Analyst   = 'analyst';
    case QAOwner   = 'qa_owner';

    public function label(): string
    {
        return match($this) {
            self::Developer => 'Developer',
            self::Tester    => 'Tester',
            self::Analyst   => 'Analyst',
            self::QAOwner   => 'QA Owner',
        };
    }
}
