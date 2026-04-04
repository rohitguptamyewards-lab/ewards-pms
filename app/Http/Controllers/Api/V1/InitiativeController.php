<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInitiativeRequest;
use App\Repositories\InitiativeRepository;
use App\Services\InitiativeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Facades\DB;

class InitiativeController extends Controller
{
    public function __construct(
        protected InitiativeRepository $repository,
        protected InitiativeService $service
    ) {}

    // ── Web Routes ──────────────────────────────────────────

    public function index(Request $request): InertiaResponse
    {
        $filters = $request->only(['status', 'origin_type', 'owner_id', 'module_id']);
        $initiatives = $this->repository->findAll($filters);
        $modules = DB::table('modules')->where('is_active', true)->select('id', 'name')->get();
        $teamMembers = DB::table('team_members')->where('is_active', true)->select('id', 'name')->get();

        return Inertia::render('Initiatives/Index', [
            'initiatives' => $initiatives,
            'modules'     => $modules,
            'teamMembers' => $teamMembers,
            'filters'     => $filters,
        ]);
    }

    public function create(): InertiaResponse
    {
        $modules = DB::table('modules')->where('is_active', true)->select('id', 'name')->get();
        $teamMembers = DB::table('team_members')->where('is_active', true)->select('id', 'name', 'role')->get();

        return Inertia::render('Initiatives/Create', [
            'modules'     => $modules,
            'teamMembers' => $teamMembers,
        ]);
    }

    public function storeWeb(StoreInitiativeRequest $request)
    {
        $data = $request->validated();
        $data['tenant_id'] = null;

        $id = $this->repository->create($data);

        return redirect()->route('initiatives.show', $id)
            ->with('success', 'Initiative created successfully.');
    }

    public function showPage(int $id): InertiaResponse
    {
        $initiative = $this->service->getInitiativeWithDetails($id);

        if (! $initiative) {
            abort(404);
        }

        $user = auth()->user();
        $isManager = in_array($user->role->value ?? $user->role, ['cto', 'ceo', 'manager']);

        return Inertia::render('Initiatives/Show', [
            'initiative' => $initiative,
            'isManager'  => $isManager,
        ]);
    }

    public function updateWeb(StoreInitiativeRequest $request, int $id)
    {
        $this->repository->update($id, $request->validated());

        return redirect()->route('initiatives.show', $id)
            ->with('success', 'Initiative updated successfully.');
    }

    // ── API Routes ──────────────────────────────────────────

    public function store(StoreInitiativeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = null;

        $id = $this->repository->create($data);

        return response()->json(['id' => $id, 'message' => 'Initiative created.'], 201);
    }

    public function show(int $id): JsonResponse
    {
        $initiative = $this->service->getInitiativeWithDetails($id);

        if (! $initiative) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($initiative);
    }

    public function update(StoreInitiativeRequest $request, int $id): JsonResponse
    {
        $this->repository->update($id, $request->validated());

        return response()->json(['message' => 'Initiative updated.']);
    }
}
