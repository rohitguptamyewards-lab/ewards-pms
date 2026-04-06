<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSprintRequest;
use App\Repositories\SprintRepository;
use App\Services\SprintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SprintController extends Controller
{
    public function __construct(
        private readonly SprintService $sprintService,
        private readonly SprintRepository $sprintRepository,
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $sprints = $this->sprintRepository->paginate(
            $request->only(['status']),
            $request->integer('per_page', 15),
        );

        if ($request->wantsJson()) {
            return response()->json($sprints);
        }

        return Inertia::render('Sprints/Index', [
            'sprints'   => $sprints,
            'isManager' => $this->isManager(),
            'filters'   => $request->only(['status']),
        ]);
    }

    public function create(): InertiaResponse
    {
        abort_unless($this->isManager(), 403);

        $nextNumber = $this->sprintRepository->getNextSprintNumber();
        $features = DB::table('features')
            ->whereNull('features.deleted_at')
            ->whereNotIn('features.status', ['released', 'rejected', 'deferred'])
            ->leftJoin('modules', 'features.module_id', '=', 'modules.id')
            ->leftJoin('team_members', 'features.assigned_to', '=', 'team_members.id')
            ->select('features.id', 'features.title', 'features.priority', 'features.estimated_hours',
                     'features.status', 'modules.name as module_name', 'team_members.name as assignee_name')
            ->orderBy('features.priority')
            ->get();

        return Inertia::render('Sprints/Create', [
            'nextNumber' => $nextNumber,
            'features'   => $features,
        ]);
    }

    public function storeWeb(StoreSprintRequest $request)
    {
        abort_unless($this->isManager(), 403);

        $data = $request->validated();
        $id = $this->sprintService->create($data);

        // Add selected features
        $featureIds = $request->input('feature_ids', []);
        foreach ($featureIds as $featureId) {
            $hours = $request->input("feature_hours.{$featureId}", 0);
            $this->sprintService->addFeature($id, [
                'feature_id'     => $featureId,
                'committed_hours'=> $hours,
            ]);
        }

        return redirect()->route('sprints.show', $id)->with('success', 'Sprint created.');
    }

    public function showPage(int $id): InertiaResponse
    {
        $sprint = $this->sprintRepository->findByIdWithFeatures($id);
        abort_if(!$sprint, 404);

        $health = $this->sprintService->calculateHealthScore($id);
        $velocity = $this->sprintRepository->calculateVelocity($id);

        return Inertia::render('Sprints/Show', [
            'sprint'    => $sprint,
            'health'    => $health,
            'velocity'  => $velocity,
            'isManager' => $this->isManager(),
        ]);
    }

    public function store(StoreSprintRequest $request): JsonResponse
    {
        abort_unless($this->isManager(), 403);
        $id = $this->sprintService->create($request->validated());

        return response()->json($this->sprintRepository->findById($id), 201);
    }

    public function show(int $id): JsonResponse
    {
        $sprint = $this->sprintRepository->findByIdWithFeatures($id);
        abort_if(!$sprint, 404);

        return response()->json($sprint);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($this->isManager(), 403);
        $this->sprintRepository->update($id, $request->all());

        return response()->json($this->sprintRepository->findById($id));
    }

    public function activate(int $id): JsonResponse
    {
        abort_unless($this->isManager(), 403, 'Only managers can activate sprints.');

        $this->sprintService->activate($id);

        return response()->json(['message' => 'Sprint activated.']);
    }

    public function complete(int $id): JsonResponse
    {
        abort_unless($this->isManager(), 403, 'Only managers can complete sprints.');

        $this->sprintService->complete($id);

        return response()->json(['message' => 'Sprint completed.']);
    }

    public function addFeature(Request $request, int $id): JsonResponse
    {
        abort_unless($this->isManager(), 403, 'Only managers can add features to sprints.');

        $data = $request->validate([
            'feature_id'      => 'required|integer|exists:features,id',
            'committed_hours' => 'nullable|integer|min:0',
        ]);

        $this->sprintService->addFeature($id, $data);

        return response()->json(['message' => 'Feature added to sprint.']);
    }

    public function removeFeature(int $id, int $featureId): JsonResponse
    {
        abort_unless($this->isManager(), 403, 'Only managers can remove features from sprints.');

        $this->sprintRepository->removeFeature($id, $featureId);

        return response()->json(['message' => 'Feature removed from sprint.']);
    }
}
