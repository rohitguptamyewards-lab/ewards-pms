<?php

namespace App\Enums;

enum GitEventType: string
{
    case Push                = 'push';
    case PullRequestOpened   = 'pull_request_opened';
    case PullRequestApproved = 'pull_request_approved';
    case PullRequestMerged   = 'pull_request_merged';
    case TagCreated          = 'tag_created';
    case BranchCreated       = 'branch_created';

    public function label(): string
    {
        return match($this) {
            self::Push                => 'Push',
            self::PullRequestOpened   => 'PR Opened',
            self::PullRequestApproved => 'PR Approved',
            self::PullRequestMerged   => 'PR Merged',
            self::TagCreated          => 'Tag Created',
            self::BranchCreated       => 'Branch Created',
        };
    }
}
