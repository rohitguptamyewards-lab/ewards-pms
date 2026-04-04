<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePromptTemplateRequest;
use App\Repositories\AiToolRepository;
use App\Repositories\PromptTemplateRepository;
use App\Services\PromptTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PromptTemplateController extends Controller
{
    public function __construct(
        private readonly PromptTemplateService $promptTemplateService,
        private readonly PromptTemplateRepository $promptTemplateRepository,
        private readonly AiToolRepository $aiToolRepository,
    ) {}

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $templates = $this->promptTemplateRepository->findAll(
            $request->only(['capability', 'ai_tool_id']),
            $request->integer('per_page', 20),
        );

        if ($request->wantsJson()) {
            return response()->json($templates);
        }

        $tools = $this->aiToolRepository->findActive();

        return Inertia::render('AiIntelligence/Templates', [
            'templates' => $templates,
            'tools'     => $tools,
            'filters'   => $request->only(['capability', 'ai_tool_id']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $tools = $this->aiToolRepository->findActive();

        return Inertia::render('AiIntelligence/TemplateCreate', [
            'tools' => $tools,
        ]);
    }

    public function storeWeb(StorePromptTemplateRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $this->promptTemplateService->create($data);

        return redirect()->route('prompt-templates.index')->with('success', 'Template created.');
    }

    public function store(StorePromptTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $id = $this->promptTemplateService->create($data);

        return response()->json($this->promptTemplateRepository->findById($id), 201);
    }

    public function update(int $id, StorePromptTemplateRequest $request): JsonResponse
    {
        $this->promptTemplateService->update($id, $request->validated());

        return response()->json($this->promptTemplateRepository->findById($id));
    }

    public function mostUsed(): JsonResponse
    {
        return response()->json($this->promptTemplateRepository->getMostUsed());
    }
}
