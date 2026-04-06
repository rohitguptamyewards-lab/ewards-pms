<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    /**
     * Render the appropriate dashboard based on user role.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = auth()->user();
        $role = $user->role instanceof Role ? $user->role->value : (string) $user->role;

        // CTO + Manager → execution view
        if (in_array($role, ['cto', 'manager'])) {
            $data = $this->dashboardService->assembleManager();
            return Inertia::render('Dashboard/Manager', [
                'activeProjects'     => $data['activeProjects']['count'],
                'activeProjectsList' => $data['activeProjects']['list'],
                'openTasks'          => $data['openTasks'],
                'blockedTasks'       => $data['blockedTasks'],
                'overdueTasks'       => $data['overdueTasks'],
                'teamWorkload'       => $data['teamWorkload'],
                'untriagedRequests'  => $data['untriagedRequests'],
            ]);
        }

        // CEO → business / pipeline view (enhanced)
        if ($role === 'ceo') {
            $data = $this->dashboardService->assembleCEOEnhanced();
            return Inertia::render('Dashboard/CEO', [
                'featurePipeline'           => $data['featurePipeline'],
                'requestPipeline'           => $data['requestPipeline'],
                'activeProjects'            => $data['activeProjects'],
                'teamSize'                  => $data['teamSize'],
                'hoursThisMonth'            => $data['hoursThisMonth'],
                'narrative'                 => $data['narrative'],
                'investmentMatrix'          => $data['investment_matrix'],
                'merchantTierStats'         => $data['merchant_tier_stats'],
                'featuresWithCtoEstimate'   => $data['features_with_cto_estimate'],
                'dataFreshness'             => $data['data_freshness'],
            ]);
        }

        // MC Team → request triage + merchant-blocked view
        if ($role === 'mc_team') {
            $data = $this->dashboardService->assembleMCTeam();
            return Inertia::render('Dashboard/MCTeam', [
                'untriagedRequests'       => $data['untriagedRequests'],
                'merchantBlockedRequests' => $data['merchantBlockedRequests'],
                'stats'                   => $data['stats'],
            ]);
        }

        // Sales → my submitted requests (enhanced)
        if ($role === 'sales') {
            $data = $this->dashboardService->assembleSales($user->id);
            return Inertia::render('Dashboard/Sales', [
                'myRequests'         => $data['myRequests'],
                'stats'              => $data['stats'],
                'merchantHistory'    => $data['merchant_history'],
                'newProductFeatures' => $data['new_product_features'],
            ]);
        }

        // Tester → QA-focused individual view
        if ($role === 'tester') {
            $data = $this->dashboardService->assembleIndividual($user->id);
            return Inertia::render('Dashboard/Tester', [
                'todaysLogs'  => $data['todaysLogs'],
                'myTasks'     => $data['myTasks'],
                'myProjects'  => $data['myProjects'],
                'weeklyHours' => $data['weeklyHours'],
            ]);
        }

        // Analyst → data/reports-focused individual view (enhanced)
        if ($role === 'analyst') {
            $data = $this->dashboardService->assembleAnalyst($user->id);
            return Inertia::render('Dashboard/Analyst', [
                'todaysLogs'          => $data['todaysLogs'],
                'myTasks'             => $data['myTasks'],
                'myProjects'          => $data['myProjects'],
                'weeklyHours'         => $data['weeklyHours'],
                'specQueue'           => $data['spec_queue'],
                'specQualityMetrics'  => $data['spec_quality_metrics'],
                'docCoverage'         => $data['doc_coverage'],
                'testCoverage'        => $data['test_coverage'],
            ]);
        }

        // Developer + fallback → individual work view (enhanced)
        $data = $this->dashboardService->assembleDeveloper($user->id);
        return Inertia::render('Dashboard/Individual', [
            'todaysLogs'           => $data['todaysLogs'],
            'myTasks'              => $data['myTasks'],
            'myProjects'           => $data['myProjects'],
            'weeklyHours'          => $data['weeklyHours'],
            'contextSwitchWarning' => $data['context_switch_warning'],
            'featuresThisWeek'     => $data['features_this_week'],
            'estimationAccuracy'   => $data['estimation_accuracy'],
            'sprintKanban'         => $data['sprint_kanban'],
            'sprintCommitment'     => $data['sprint_commitment'],
            'activeSprint'         => $data['active_sprint'],
            'isNewHire'            => $data['is_new_hire'],
            'onboardingStatus'     => $data['onboarding_status'],
        ]);
    }

    /**
     * JSON endpoint for individual dashboard data.
     * Non-managers can only view their own data.
     */
    public function individual(Request $request): JsonResponse
    {
        $userRole = auth()->user()->role;
        $role = $userRole instanceof Role ? $userRole->value : (string) $userRole;
        $isManager = in_array($role, ['cto', 'ceo', 'manager']);

        $userId = $isManager
            ? (int) $request->input('user_id', auth()->id())
            : auth()->id();

        return response()->json(
            $this->dashboardService->assembleIndividual($userId),
        );
    }

    /**
     * JSON endpoint for manager dashboard data.
     * Only CTO, CEO, Manager can access.
     */
    public function manager(): JsonResponse
    {
        $userRole = auth()->user()->role;
        $role = $userRole instanceof Role ? $userRole->value : (string) $userRole;
        abort_unless(in_array($role, ['cto', 'ceo', 'manager']), 403, 'Only managers can access manager dashboard.');

        return response()->json(
            $this->dashboardService->assembleManager(),
        );
    }
}
