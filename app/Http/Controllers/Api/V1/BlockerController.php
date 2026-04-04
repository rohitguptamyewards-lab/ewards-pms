<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlockerRequest;
use App\Repositories\BlockerRepository;
use App\Services\BlockerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockerController extends Controller
{
    public function __construct(
        private readonly BlockerService $blockerService,
        private readonly BlockerRepository $blockerRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'feature_id']);
        $blockers = $this->blockerRepository->paginate($filters, $request->integer('per_page', 15));

        return response()->json($blockers);
    }

    public function store(StoreBlockerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['team_member_id'] = auth()->id();

        $id = $this->blockerService->create($data);

        return response()->json($this->blockerRepository->findById($id), 201);
    }

    public function resolve(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'resolution_note' => 'required|string|max:2000',
        ]);

        $this->blockerService->resolve($id, auth()->id(), $request->input('resolution_note'));

        return response()->json($this->blockerRepository->findById($id));
    }

    public function byFeature(int $featureId): JsonResponse
    {
        $blockers = $this->blockerRepository->findActiveByFeature($featureId);

        return response()->json($blockers);
    }
}
