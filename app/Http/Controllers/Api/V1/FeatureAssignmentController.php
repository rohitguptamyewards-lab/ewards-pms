<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeatureAssignmentRequest;
use App\Repositories\FeatureAssignmentRepository;
use App\Services\FeatureAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatureAssignmentController extends Controller
{
    public function __construct(
        private readonly FeatureAssignmentService $assignmentService,
        private readonly FeatureAssignmentRepository $assignmentRepository,
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    public function index(Request $request): JsonResponse
    {
        $assignments = $this->assignmentRepository->findAll(
            $request->only(['feature_id', 'team_member_id', 'state', 'role']),
            $request->integer('per_page', 20),
        );

        return response()->json($assignments);
    }

    public function byFeature(int $featureId): JsonResponse
    {
        return response()->json($this->assignmentRepository->findByFeature($featureId));
    }

    public function store(StoreFeatureAssignmentRequest $request): JsonResponse
    {
        abort_unless($this->isManager(), 403, 'Only managers can assign features.');

        $data = $request->validated();
        $id = $this->assignmentService->create($data);

        return response()->json($this->assignmentRepository->findById($id), 201);
    }

    public function complete(int $id): JsonResponse
    {
        $this->assignmentService->complete($id);

        return response()->json($this->assignmentRepository->findById($id));
    }

    public function remove(int $id): JsonResponse
    {
        abort_unless($this->isManager(), 403, 'Only managers can remove feature assignments.');

        $this->assignmentService->remove($id);

        return response()->json(['message' => 'Assignment removed']);
    }

    public function estimationAccuracy(int $memberId): JsonResponse
    {
        $accuracy = $this->assignmentService->getEstimationAccuracy($memberId);

        return response()->json($accuracy);
    }
}
