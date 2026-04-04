<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDecisionRequest;
use App\Repositories\DecisionRepository;
use App\Services\DecisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Facades\DB;

class DecisionController extends Controller
{
    public function __construct(
        protected DecisionRepository $repository,
        protected DecisionService $service
    ) {}

    // ── Web Routes ──────────────────────────────────────────

    public function index(Request $request): InertiaResponse
    {
        $filters = $request->only(['status', 'linked_to_type']);
        $decisions = $this->repository->findAll($filters);

        return Inertia::render('Decisions/Index', [
            'decisions' => $decisions,
            'filters'   => $filters,
        ]);
    }

    public function create(): InertiaResponse
    {
        $teamMembers = DB::table('team_members')->where('is_active', true)->select('id', 'name', 'role')->get();
        $features = DB::table('features')->whereNull('deleted_at')->select('id', 'title')->orderBy('title')->get();
        $initiatives = DB::table('initiatives')->whereNull('deleted_at')->select('id', 'title')->orderBy('title')->get();
        $modules = DB::table('modules')->where('is_active', true)->select('id', 'name')->get();

        return Inertia::render('Decisions/Create', [
            'teamMembers' => $teamMembers,
            'features'    => $features,
            'initiatives' => $initiatives,
            'modules'     => $modules,
        ]);
    }

    public function storeWeb(StoreDecisionRequest $request)
    {
        $data = $request->validated();
        $id = $this->repository->create($data);

        return redirect()->route('decisions.show', $id)
            ->with('success', 'Decision recorded successfully.');
    }

    public function showPage(int $id): InertiaResponse
    {
        $decision = $this->repository->findById($id);

        if (! $decision) {
            abort(404);
        }

        // Get decisions that this supersedes
        $supersededDecisions = DB::table('decisions')
            ->where('superseded_by', $id)
            ->whereNull('deleted_at')
            ->select('id', 'title', 'decision_date')
            ->get();

        return Inertia::render('Decisions/Show', [
            'decision'            => $decision,
            'supersededDecisions' => $supersededDecisions,
        ]);
    }

    // ── API Routes ──────────────────────────────────────────

    public function store(StoreDecisionRequest $request): JsonResponse
    {
        $id = $this->repository->create($request->validated());

        return response()->json(['id' => $id, 'message' => 'Decision recorded.'], 201);
    }

    public function show(int $id): JsonResponse
    {
        $decision = $this->repository->findById($id);

        if (! $decision) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($decision);
    }

    /**
     * BR-032: Decisions cannot be updated — only superseded.
     */
    public function supersede(StoreDecisionRequest $request, int $id): JsonResponse
    {
        $newId = $this->service->supersedeDecision($id, $request->validated());

        return response()->json([
            'id'      => $newId,
            'message' => 'Decision superseded. New decision created.',
        ], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:proposed,open,decided'],
        ]);

        $this->repository->updateStatus($id, $data['status']);

        return response()->json(['message' => 'Status updated.']);
    }
}
