<?php

namespace App\Enums;

enum ActivityTypeDeveloper: string
{
    case Coding                 = 'coding';
    case BugFixing              = 'bug_fixing';
    case CodeReview             = 'code_review';
    case InvestigationDebugging = 'investigation_debugging';
    case Deployment             = 'deployment';
    case Documentation          = 'documentation';
    case Meeting                = 'meeting';

    public function label(): string
    {
        return match($this) {
            self::Coding                 => 'Coding',
            self::BugFixing              => 'Bug Fixing',
            self::CodeReview             => 'Code Review',
            self::InvestigationDebugging => 'Investigation & Debugging',
            self::Deployment             => 'Deployment',
            self::Documentation          => 'Documentation',
            self::Meeting                => 'Meeting',
        };
    }
}
