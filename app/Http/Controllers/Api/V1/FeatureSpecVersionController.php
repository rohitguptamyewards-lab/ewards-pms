<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeatureSpecVersionRequest;
use App\Repositories\FeatureSpecVersionRepository;
use App\Services\FeatureSpecVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatureSpecVersionController extends Controller
{
    public function __construct(
        private readonly FeatureSpecVersionService $specService,
        private readonly FeatureSpecVersionRepository $specRepo,
    ) {}

    public function index(int $featureId): JsonResponse
    {
        $versions = $this->specRepo->findByFeature($featureId);

        return response()->json($versions);
    }

    public function store(StoreFeatureSpecVersionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['author_id'] = auth()->id();

        $id = $this->specService->create($data);

        return response()->json($this->specRepo->findById($id), 201);
    }

    public function show(int $id): JsonResponse
    {
        $spec = $this->specRepo->findById($id);
        abort_if(!$spec, 404);

        return response()->json($spec);
    }

    public function freeze(int $id): JsonResponse
    {
        $this->specService->freeze($id);

        return response()->json(['message' => 'Spec version frozen.']);
    }

    public function acknowledge(int $id): JsonResponse
    {
        $this->specService->acknowledge($id, auth()->id());

        return response()->json(['message' => 'Spec acknowledged.']);
    }

    public function submitForReview(int $id): JsonResponse
    {
        $this->specService->submitForReview($id);

        return response()->json(['message' => 'Spec submitted for review.']);
    }
}
