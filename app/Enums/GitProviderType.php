<?php

namespace App\Enums;

enum GitProviderType: string
{
    case GitHub      = 'github';
    case CodeCommit  = 'codecommit';
    case GitLab      = 'gitlab';

    public function label(): string
    {
        return match($this) {
            self::GitHub     => 'GitHub',
            self::CodeCommit => 'AWS CodeCommit',
            self::GitLab     => 'GitLab',
        };
    }
}
