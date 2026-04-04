<?php

namespace App\Enums;

enum ActivityTypeTester: string
{
    case TestCaseWriting   = 'test_case_writing';
    case ManualTesting     = 'manual_testing';
    case BugReporting      = 'bug_reporting';
    case RegressionTesting = 'regression_testing';
    case Verification      = 'verification';
    case EnvironmentSetup  = 'environment_setup';
    case Meeting           = 'meeting';

    public function label(): string
    {
        return match($this) {
            self::TestCaseWriting   => 'Test Case Writing',
            self::ManualTesting     => 'Manual Testing',
            self::BugReporting      => 'Bug Reporting',
            self::RegressionTesting => 'Regression Testing',
            self::Verification      => 'Verification',
            self::EnvironmentSetup  => 'Environment Setup',
            self::Meeting           => 'Meeting',
        };
    }
}
