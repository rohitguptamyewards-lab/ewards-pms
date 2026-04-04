<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BusFactorController extends Controller
{
    /**
     * Bus Factor dashboard — identifies knowledge concentration risks.
     */
    public function index(): InertiaResponse
    {
        // Module ownership concentration
        $moduleOwnership = DB::table('features')
            ->join('modules', 'features.module_id', '=', 'modules.id')
            ->join('team_members', 'features.assigned_to', '=', 'team_members.id')
            ->select(
                'modules.id as module_id',
                'modules.name as module_name',
                'team_members.id as member_id',
                'team_members.name as member_name',
                DB::raw('COUNT(*) as feature_count')
            )
            ->whereNull('features.deleted_at')
            ->whereNotNull('features.assigned_to')
            ->whereNotNull('features.module_id')
            ->groupBy('modules.id', 'modules.name', 'team_members.id', 'team_members.name')
            ->orderBy('modules.name')
            ->orderByDesc('feature_count')
            ->get();

        // Group by module to calculate bus factor
        $modules = [];
        foreach ($moduleOwnership as $row) {
            if (!isset($modules[$row->module_id])) {
                $modules[$row->module_id] = [
                    'module_name'   => $row->module_name,
                    'contributors'  => [],
                    'total_features'=> 0,
                ];
            }
            $modules[$row->module_id]['contributors'][] = [
                'name'          => $row->member_name,
                'feature_count' => $row->feature_count,
            ];
            $modules[$row->module_id]['total_features'] += $row->feature_count;
        }

        // Calculate bus factor per module
        $busFactorData = [];
        foreach ($modules as $modId => $mod) {
            $total = $mod['total_features'];
            $contributors = $mod['contributors'];
            $busFactor = count($contributors);

            // Check if top contributor owns > 60% of features
            $topContrib = $contributors[0] ?? null;
            $isRisky = $topContrib && ($topContrib['feature_count'] / max($total, 1)) > 0.6;

            $busFactorData[] = [
                'module_name'    => $mod['module_name'],
                'bus_factor'     => $busFactor,
                'total_features' => $total,
                'contributors'   => $contributors,
                'is_risky'       => $isRisky,
                'risk_level'     => $busFactor === 1 ? 'critical' : ($isRisky ? 'high' : ($busFactor <= 2 ? 'medium' : 'low')),
            ];
        }

        // Sort by risk (critical first)
        usort($busFactorData, function ($a, $b) {
            $riskOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return ($riskOrder[$a['risk_level']] ?? 4) <=> ($riskOrder[$b['risk_level']] ?? 4);
        });

        // Cross-training recommendations
        $singleOwnerModules = array_filter($busFactorData, fn ($m) => $m['bus_factor'] <= 1);
        $availableMembers = DB::table('team_members')
            ->where('is_active', true)
            ->whereIn('role', ['developer', 'tester'])
            ->select('id', 'name', 'role', 'skill_tags', 'module_expertise')
            ->orderBy('name')
            ->get()
            ->toArray();

        return Inertia::render('BusFactor/Index', [
            'busFactorData'       => array_values($busFactorData),
            'singleOwnerModules'  => array_values($singleOwnerModules),
            'availableMembers'    => $availableMembers,
        ]);
    }
}
