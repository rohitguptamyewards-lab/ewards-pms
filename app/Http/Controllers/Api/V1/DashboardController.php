<?php

namespace App\Http\Controllers\Api\V1;

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
        $role = $user->role->value ?? $user->role;

        if (in_array($role, ['cto', 'ceo'])) {
            $data = $this->dashboardService->assembleManager();

            return Inertia::render('Dashboard/Manager', [
                'activeProjects' => $data['activeProjects']['count'],
                'activeProjectsList' => $data['activeProjects']['list'],
                'openTasks' => $data['openTasks'],
                'blockedTasks' => $data['blockedTasks'],
                'overdueTasks' => $data['overdueTasks'],
                'teamWorkload' => $data['teamWorkload'],
                'untriagedRequests' => $data['untriagedRequests'],
            ]);
        }

        $data = $this->dashboardService->assembleIndividual($user->id);

        return Inertia::render('Dashboard/Individual', [
            'todaysLogs' => $data['todaysLogs'],
            'myTasks' => $data['myTasks'],
            'myProjects' => $data['myProjects'],
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
