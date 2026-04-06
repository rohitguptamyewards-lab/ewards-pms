<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CapacityPlanningService;
use App\Services\VelocityForecastingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CapacityPlanningController extends Controller
{
    public function __construct(
        private readonly CapacityPlanningService $capacityService,
        private readonly VelocityForecastingService $velocityService,
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        abort_unless($this->isManager(), 403);

        $startDate = $request->input('start_date', now()->startOfWeek()->toDateString());
        $endDate   = $request->input('end_date', now()->endOfWeek()->toDateString());

        $capacity = $this->capacityService->getCapacityOverview($startDate, $endDate);
        $velocity = $this->velocityService->getVelocityBaseline();

        if ($request->wantsJson()) {
            return response()->json(compact('capacity', 'velocity'));
        }

        return Inertia::render('Capacity/Index', [
            'capacity'  => $capacity,
            'velocity'  => $velocity,
            'filters'   => compact('startDate', 'endDate'),
        ]);
    }

    public function forecast(int $initiativeId): JsonResponse
    {
        abort_unless($this->isManager(), 403, 'Only managers can view forecasts.');

        $forecast = $this->velocityService->forecastInitiativeCompletion($initiativeId);
        $scope    = $this->velocityService->getScopeCreep($initiativeId);

        return response()->json(compact('forecast', 'scope'));
    }
}
