<?php

namespace App\Enums;

enum BugRootCause: string
{
    case SpecGap    = 'spec_gap';
    case CodeError  = 'code_error';
    case Environment = 'environment';
    case ThirdParty = 'third_party';

    public function label(): string
    {
        return match($this) {
            self::SpecGap     => 'Spec Gap',
            self::CodeError   => 'Code Error',
            self::Environment => 'Environment',
            self::ThirdParty  => 'Third-Party',
        };
    }
}
