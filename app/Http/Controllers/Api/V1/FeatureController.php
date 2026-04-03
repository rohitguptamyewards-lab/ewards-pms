<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\FeatureRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FeatureController extends Controller
{
    public function __construct(
        private readonly FeatureRepository $featureRepository,
    ) {}

    /**
     * List features.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $features = $this->featureRepository->findAll(
            $request->only(['status', 'module_id', 'priority', 'search']),
            $request->integer('per_page', 15),
        );

        if ($request->wantsJson()) {
            return response()->json($features);
        }

        return Inertia::render('Features/Index', [
            'features' => $features,
        ]);
    }

    /**
     * Store a new feature.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string',
            'priority' => 'nullable|string|in:p0,p1,p2,p3',
            'module_id' => 'nullable|integer|exists:modules,id',
            'status' => 'nullable|string',
            'deadline' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|integer|exists:team_members,id',
        ]);

        $id = $this->featureRepository->create($data);
        $feature = $this->featureRepository->findById($id);

        return response()->json($feature, 201);
    }

    /**
     * Show a single feature.
     */
    public function show(int $id): JsonResponse
    {
        $feature = $this->featureRepository->findById($id);

        return response()->json($feature);
    }

    /**
     * Update a feature.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->featureRepository->update($id, $request->all());
        $feature = $this->featureRepository->findById($id);

        return response()->json($feature);
    }
}
