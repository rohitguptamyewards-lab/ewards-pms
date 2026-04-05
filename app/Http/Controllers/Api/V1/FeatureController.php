<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\FeatureRepository;
use App\Services\EmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FeatureController extends Controller
{
    public function __construct(
        private readonly FeatureRepository $featureRepository,
        private readonly EmailNotificationService $emailService,
    ) {}

    private function managerRoles(): array
    {
        return ['cto', 'ceo', 'manager'];
    }

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, $this->managerRoles());
    }

    private function dropdowns(): array
    {
        return [
            'modules'      => DB::table('modules')->orderBy('name')->select('id', 'name')->get(),
            'teamMembers'  => DB::table('team_members')->where('is_active', true)->orderBy('name')->select('id', 'name')->get(),
            'initiatives'  => DB::table('initiatives')->whereNull('deleted_at')->orderBy('title')->select('id', 'title')->get(),
        ];
    }

    /**
     * List features.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $features = $this->featureRepository->findAll(
            $request->only(['status', 'module_id', 'priority', 'search', 'origin_type', 'initiative_id']),
            $request->integer('per_page', 15),
        );

        if ($request->wantsJson()) {
            return response()->json($features);
        }

        return Inertia::render('Features/Index', [
            'features'  => $features,
            'isManager' => $this->isManager(),
            'filters'   => $request->only(['status', 'module_id', 'priority']),
            ...$this->dropdowns(),
        ]);
    }

    /**
     * Kanban board view — all non-deleted features grouped by status.
     */
    public function kanban(): InertiaResponse
    {
        $features = DB::table('features')
            ->leftJoin('modules', 'features.module_id', '=', 'modules.id')
            ->leftJoin('team_members', 'features.assigned_to', '=', 'team_members.id')
            ->select(
                'features.id', 'features.title', 'features.status', 'features.priority',
                'features.deadline', 'features.assigned_to',
                'modules.name as module_name',
                'team_members.name as assignee_name'
            )
            ->whereNull('features.deleted_at')
            ->orderByDesc('features.updated_at')
            ->get();

        return Inertia::render('Features/Kanban', [
            'features'  => $features,
            'isManager' => $this->isManager(),
        ]);
    }

    /**
     * Show the create form (web).
     */
    public function create(): InertiaResponse
    {
        abort_unless($this->isManager(), 403);

        return Inertia::render('Features/Create', $this->dropdowns());
    }

    /**
     * Store a new feature via web form.
     */
    public function storeWeb(Request $request)
    {
        abort_unless($this->isManager(), 403);

        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'type'            => 'nullable|string|in:bug,improvement,new_feature',
            'priority'        => 'nullable|string|in:p0,p1,p2,p3',
            'module_id'       => 'nullable|integer|exists:modules,id',
            'initiative_id'   => 'nullable|integer|exists:initiatives,id',
            'origin_type'     => 'nullable|string|in:request,initiative,tech_debt,bug,idea',
            'rollout_state'   => 'nullable|string|in:internal,beta_pilot,gradual_ga,full_ga,sunset',
            'status'          => 'nullable|string',
            'deadline'        => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'assigned_to'     => 'nullable|integer|exists:team_members,id',
            'qa_owner_id'     => 'nullable|integer|exists:team_members,id',
            'business_impact' => 'nullable|string',
        ]);

        $data['status'] = $data['status'] ?? 'backlog';
        $id = $this->featureRepository->create($data);

        return redirect()->route('features.show', $id)->with('success', 'Feature created.');
    }

    /**
     * Show a single feature (Inertia page).
     */
    public function showPage(Request $request, int $id): InertiaResponse
    {
        $feature = $this->featureRepository->findById($id);
        abort_if(!$feature, 404);

        return Inertia::render('Features/Show', [
            'feature'   => $feature,
            'isManager' => $this->isManager(),
            ...$this->dropdowns(),
        ]);
    }

    /**
     * Update a feature via web form (status transition or edit).
     */
    public function updateWeb(Request $request, int $id)
    {
        abort_unless($this->isManager(), 403);

        $data = $request->validate([
            'title'           => 'sometimes|string|max:255',
            'description'     => 'nullable|string',
            'type'            => 'nullable|string|in:bug,improvement,new_feature',
            'priority'        => 'nullable|string|in:p0,p1,p2,p3',
            'module_id'       => 'nullable|integer|exists:modules,id',
            'initiative_id'   => 'nullable|integer|exists:initiatives,id',
            'origin_type'     => 'nullable|string|in:request,initiative,tech_debt,bug,idea',
            'rollout_state'   => 'nullable|string|in:internal,beta_pilot,gradual_ga,full_ga,sunset',
            'status'          => 'nullable|string',
            'deadline'        => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'assigned_to'     => 'nullable|integer|exists:team_members,id',
            'qa_owner_id'     => 'nullable|integer|exists:team_members,id',
            'business_impact' => 'nullable|string',
        ]);

        $oldFeature = $this->featureRepository->findById($id);
        $oldStatus = $oldFeature->status ?? null;
        $oldAssignee = $oldFeature->assigned_to ?? null;
        $oldQaOwner = $oldFeature->qa_owner_id ?? null;

        $this->featureRepository->update($id, $data);

        // Send email if status changed
        $newStatus = $data['status'] ?? null;
        if ($newStatus && $oldStatus && $newStatus !== $oldStatus) {
            try {
                $this->emailService->onFeatureStatusChanged($id, $oldStatus, $newStatus, auth()->id());
            } catch (\Throwable $e) {}
        }

        // Send email if assignee changed
        $newAssignee = $data['assigned_to'] ?? null;
        if ($newAssignee && (int) $newAssignee !== (int) $oldAssignee) {
            try {
                $this->emailService->onFeatureAssigned($id, (int) $newAssignee, auth()->id());
            } catch (\Throwable $e) {}
        }

        // Send email if QA owner changed
        $newQaOwner = $data['qa_owner_id'] ?? null;
        if ($newQaOwner && (int) $newQaOwner !== (int) $oldQaOwner) {
            try {
                $this->emailService->onFeatureQaAssigned($id, (int) $newQaOwner, auth()->id());
            } catch (\Throwable $e) {}
        }

        return redirect()->route('features.show', $id)->with('success', 'Feature updated.');
    }

    /**
     * Store a new feature via API (JSON).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'type'            => 'nullable|string',
            'priority'        => 'nullable|string|in:p0,p1,p2,p3',
            'module_id'       => 'nullable|integer|exists:modules,id',
            'status'          => 'nullable|string',
            'deadline'        => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'assigned_to'     => 'nullable|integer|exists:team_members,id',
        ]);

        $id = $this->featureRepository->create($data);

        return response()->json($this->featureRepository->findById($id), 201);
    }

    /**
     * Show a single feature via API (JSON).
     */
    public function show(int $id): JsonResponse
    {
        return response()->json($this->featureRepository->findById($id));
    }

    /**
     * Update a feature via API (JSON).
     * Only managers can update features.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($this->isManager(), 403, 'Only managers can update features.');

        $this->featureRepository->update($id, $request->all());

        return response()->json($this->featureRepository->findById($id));
    }
}
