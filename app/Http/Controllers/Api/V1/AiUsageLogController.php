<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAiUsageLogRequest;
use App\Repositories\AiUsageLogRepository;
use App\Services\AiUsageLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiUsageLogController extends Controller
{
    public function __construct(
        private readonly AiUsageLogService $aiUsageLogService,
        private readonly AiUsageLogRepository $aiUsageLogRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $logs = $this->aiUsageLogRepository->findAll(
            $request->only(['ai_tool_id', 'capability', 'outcome']),
            $request->integer('per_page', 20),
        );

        return response()->json($logs);
    }

    public function store(StoreAiUsageLogRequest $request): JsonResponse
    {
        $data = $request->validated();
        $id = $this->aiUsageLogService->create($data);

        return response()->json(['id' => $id], 201);
    }

    public function knowledgeBase(Request $request): JsonResponse
    {
        $items = $this->aiUsageLogService->getKnowledgeBase(
            $request->only(['capability', 'ai_tool_id'])
        );

        return response()->json($items);
    }

    public function effectivenessMatrix(Request $request): JsonResponse
    {
        $matrix = $this->aiUsageLogService->getEffectivenessMatrix(
            $request->only(['ai_tool_id'])
        );

        return response()->json($matrix);
    }
}
