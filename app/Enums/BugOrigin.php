<?php

namespace App\Enums;

enum BugOrigin: string
{
    case QAFound          = 'qa_found';
    case ProductionFound  = 'production_found';

    public function label(): string
    {
        return match($this) {
            self::QAFound         => 'QA Found',
            self::ProductionFound => 'Production Found',
        };
    }
}
