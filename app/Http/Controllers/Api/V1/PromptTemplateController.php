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

    private function isCto(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return $value === 'cto';
    }

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
        abort_unless($this->isCto(), 403, 'Only CTO can manage prompt templates.');

        $tools = $this->aiToolRepository->findActive();

        return Inertia::render('AiIntelligence/TemplateCreate', [
            'tools' => $tools,
        ]);
    }

    public function storeWeb(StorePromptTemplateRequest $request)
    {
        abort_unless($this->isCto(), 403, 'Only CTO can manage prompt templates.');

        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $this->promptTemplateService->create($data);

        return redirect()->route('prompt-templates.index')->with('success', 'Template created.');
    }

    public function store(StorePromptTemplateRequest $request): JsonResponse
    {
        abort_unless($this->isCto(), 403, 'Only CTO can manage prompt templates.');

        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $id = $this->promptTemplateService->create($data);

        return response()->json($this->promptTemplateRepository->findById($id), 201);
    }

    public function update(int $id, StorePromptTemplateRequest $request): JsonResponse
    {
        abort_unless($this->isCto(), 403, 'Only CTO can manage prompt templates.');

        $this->promptTemplateService->update($id, $request->validated());

        return response()->json($this->promptTemplateRepository->findById($id));
    }

    public function mostUsed(): JsonResponse
    {
        return response()->json($this->promptTemplateRepository->getMostUsed());
    }
}
