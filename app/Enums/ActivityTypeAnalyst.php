<?php

namespace App\Enums;

enum ActivityTypeAnalyst: string
{
    case SpecificationWriting  = 'specification_writing';
    case RequirementGathering  = 'requirement_gathering';
    case TechnicalDocumentation = 'technical_documentation';
    case TestScenarioWriting   = 'test_scenario_writing';
    case SpecReviewRevision    = 'spec_review_revision';
    case Research              = 'research';
    case Meeting               = 'meeting';

    public function label(): string
    {
        return match($this) {
            self::SpecificationWriting   => 'Specification Writing',
            self::RequirementGathering   => 'Requirement Gathering',
            self::TechnicalDocumentation => 'Technical Documentation',
            self::TestScenarioWriting    => 'Test Scenario Writing',
            self::SpecReviewRevision     => 'Spec Review and Revision',
            self::Research               => 'Research',
            self::Meeting                => 'Meeting',
        };
    }
}
