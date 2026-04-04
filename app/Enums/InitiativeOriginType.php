<?php

namespace App\Enums;

enum InitiativeOriginType: string
{
    case Strategic = 'strategic';
    case DataInsight = 'data_insight';
    case TechDebt = 'tech_debt';
    case ClientDemand = 'client_demand';

    public function label(): string
    {
        return match ($this) {
            self::Strategic    => 'Strategic',
            self::DataInsight  => 'Data Insight',
            self::TechDebt     => 'Tech Debt',
            self::ClientDemand => 'Client Demand',
        };
    }
}
