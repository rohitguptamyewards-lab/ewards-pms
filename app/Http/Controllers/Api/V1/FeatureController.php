<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\FeatureRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FeatureController extends Controller
{
    public function __construct(
        private readonly FeatureRepository $featureRepository,
    ) {}

    private function managerRoles(): array
    {
        return ['cto', 'ceo', 'manager', 'mc_team'];
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
            'modules'     => DB::table('modules')->orderBy('name')->select('id', 'name')->get(),
            'teamMembers' => DB::table('team_members')->where('is_active', true)->orderBy('name')->select('id', 'name')->get(),
        ];
    }

    /**
     * List features.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $features = $this->featureRepository->findAll(
            $request->only(['status', 'module_id', 'priority', 'search']),
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
            'status'          => 'nullable|string',
            'deadline'        => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'assigned_to'     => 'nullable|integer|exists:team_members,id',
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
            'status'          => 'nullable|string',
            'deadline'        => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'assigned_to'     => 'nullable|integer|exists:team_members,id',
        ]);

        $this->featureRepository->update($id, $data);

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
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->featureRepository->update($id, $request->all());

        return response()->json($this->featureRepository->findById($id));
    }
}
