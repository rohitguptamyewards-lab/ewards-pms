<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIdeaRequest;
use App\Repositories\IdeaRepository;
use App\Services\IdeaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class IdeaController extends Controller
{
    public function __construct(
        protected IdeaRepository $repository,
        protected IdeaService $service
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    // ── Web Routes ──────────────────────────────────────────

    public function index(Request $request): InertiaResponse
    {
        $filters = $request->only(['status', 'created_by']);
        $ideas = $this->repository->findAll($filters);

        return Inertia::render('Ideas/Index', [
            'ideas'   => $ideas,
            'filters' => $filters,
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Ideas/Create');
    }

    public function storeWeb(StoreIdeaRequest $request)
    {
        abort_unless($this->isManager(), 403);
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $id = $this->repository->create($data);

        return redirect()->route('ideas.index')
            ->with('success', 'Idea captured successfully.');
    }

    public function showPage(int $id): InertiaResponse
    {
        $idea = $this->repository->findById($id);

        if (! $idea) {
            abort(404);
        }

        return Inertia::render('Ideas/Show', [
            'idea' => $idea,
        ]);
    }

    // ── API Routes ──────────────────────────────────────────

    public function store(StoreIdeaRequest $request): JsonResponse
    {
        abort_unless($this->isManager(), 403);
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $id = $this->repository->create($data);

        return response()->json(['id' => $id, 'message' => 'Idea captured.'], 201);
    }

    public function show(int $id): JsonResponse
    {
        $idea = $this->repository->findById($id);

        if (! $idea) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($idea);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($this->isManager(), 403, 'Only managers can update ideas.');

        $data = $request->validate([
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'source'      => ['nullable', 'string', 'max:255'],
            'status'      => ['sometimes', 'in:new,under_review,promoted,archived'],
        ]);

        $this->repository->update($id, $data);

        return response()->json(['message' => 'Idea updated.']);
    }

    public function promote(Request $request, int $id): JsonResponse
    {
        abort_unless($this->isManager(), 403, 'Only managers can promote ideas.');

        $data = $request->validate([
            'target_type' => ['required', 'in:feature,initiative'],
            'target_id'   => ['required', 'integer'],
        ]);

        $this->service->promote($id, $data['target_type'], $data['target_id']);

        return response()->json(['message' => 'Idea promoted.']);
    }
}
