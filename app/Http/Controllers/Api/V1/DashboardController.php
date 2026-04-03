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

        // CEO → business / pipeline view
        if ($role === 'ceo') {
            $data = $this->dashboardService->assembleCEO();
            return Inertia::render('Dashboard/CEO', [
                'featurePipeline' => $data['featurePipeline'],
                'requestPipeline' => $data['requestPipeline'],
                'activeProjects'  => $data['activeProjects'],
                'teamSize'        => $data['teamSize'],
                'hoursThisMonth'  => $data['hoursThisMonth'],
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

        // Sales → my submitted requests
        if ($role === 'sales') {
            $data = $this->dashboardService->assembleSales($user->id);
            return Inertia::render('Dashboard/Sales', [
                'myRequests' => $data['myRequests'],
                'stats'      => $data['stats'],
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

        // Analyst → data/reports-focused individual view
        if ($role === 'analyst') {
            $data = $this->dashboardService->assembleIndividual($user->id);
            return Inertia::render('Dashboard/Analyst', [
                'todaysLogs'  => $data['todaysLogs'],
                'myTasks'     => $data['myTasks'],
                'myProjects'  => $data['myProjects'],
                'weeklyHours' => $data['weeklyHours'],
            ]);
        }

        // Developer + fallback → individual work view
        $data = $this->dashboardService->assembleIndividual($user->id);
        return Inertia::render('Dashboard/Individual', [
            'todaysLogs'  => $data['todaysLogs'],
            'myTasks'     => $data['myTasks'],
            'myProjects'  => $data['myProjects'],
            'weeklyHours' => $data['weeklyHours'],
        ]);
    }

    /**
     * JSON endpoint for individual dashboard data.
     */
    public function individual(Request $request): JsonResponse
    {
        $userId = $request->input('user_id', auth()->id());

        return response()->json(
            $this->dashboardService->assembleIndividual($userId),
        );
    }

    /**
     * JSON endpoint for manager dashboard data.
     */
    public function manager(): JsonResponse
    {
        return response()->json(
            $this->dashboardService->assembleManager(),
        );
    }
}
