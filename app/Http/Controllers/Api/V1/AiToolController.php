<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAiToolRequest;
use App\Repositories\AiToolAssignmentRepository;
use App\Repositories\AiToolRepository;
use App\Services\AiToolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AiToolController extends Controller
{
    public function __construct(
        private readonly AiToolService $aiToolService,
        private readonly AiToolRepository $aiToolRepository,
        private readonly AiToolAssignmentRepository $assignmentRepository,
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $tools = $this->aiToolRepository->findAll(
            $request->only(['is_active']),
            $request->integer('per_page', 20),
        );

        if ($request->wantsJson()) {
            return response()->json($tools);
        }

        return Inertia::render('AiIntelligence/Tools', [
            'tools'     => $tools,
            'isManager' => $this->isManager(),
            'filters'   => $request->only(['is_active']),
        ]);
    }

    public function create(): InertiaResponse
    {
        abort_unless($this->isManager(), 403);

        return Inertia::render('AiIntelligence/ToolCreate');
    }

    public function storeWeb(StoreAiToolRequest $request)
    {
        abort_unless($this->isManager(), 403);

        $data = $request->validated();
        $id = $this->aiToolService->create($data);

        return redirect()->route('ai-tools.index')->with('success', 'AI tool created.');
    }

    public function showPage(int $id): InertiaResponse
    {
        $tool = $this->aiToolRepository->findById($id);
        abort_if(!$tool, 404);

        $assignments = $this->assignmentRepository->findByTool($id);

        $teamMembers = DB::table('team_members')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('AiIntelligence/ToolShow', [
            'tool'        => $tool,
            'assignments' => $assignments,
            'teamMembers' => $teamMembers,
            'isManager'   => $this->isManager(),
        ]);
    }

    public function store(StoreAiToolRequest $request): JsonResponse
    {
        $data = $request->validated();
        $id = $this->aiToolService->create($data);

        return response()->json($this->aiToolRepository->findById($id), 201);
    }

    public function update(int $id, StoreAiToolRequest $request): JsonResponse
    {
        $this->aiToolService->update($id, $request->validated());

        return response()->json($this->aiToolRepository->findById($id));
    }

    public function assign(int $id, Request $request): JsonResponse
    {
        $request->validate(['team_member_id' => 'required|exists:team_members,id']);

        $this->aiToolService->assignToMember($id, $request->integer('team_member_id'));

        return response()->json(['message' => 'Assigned']);
    }

    public function revoke(int $id, Request $request): JsonResponse
    {
        $request->validate(['team_member_id' => 'required|exists:team_members,id']);

        $this->aiToolService->revokeFromMember($id, $request->integer('team_member_id'));

        return response()->json(['message' => 'Revoked']);
    }
}
