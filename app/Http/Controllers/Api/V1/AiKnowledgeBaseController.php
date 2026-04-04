<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\AiUsageLogRepository;
use App\Services\AiUsageLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AiKnowledgeBaseController extends Controller
{
    public function __construct(
        private readonly AiUsageLogService $aiUsageLogService,
        private readonly AiUsageLogRepository $aiUsageLogRepository,
    ) {}

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $items = $this->aiUsageLogService->getKnowledgeBase(
            $request->only(['capability', 'ai_tool_id', 'outcome'])
        );

        if ($request->wantsJson()) {
            return response()->json($items);
        }

        $tools = \Illuminate\Support\Facades\DB::table('ai_tools')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->select('id', 'name')
            ->get();

        return Inertia::render('AiIntelligence/KnowledgeBase', [
            'items'   => $items,
            'tools'   => $tools,
            'filters' => $request->only(['capability', 'ai_tool_id', 'outcome']),
        ]);
    }
}
