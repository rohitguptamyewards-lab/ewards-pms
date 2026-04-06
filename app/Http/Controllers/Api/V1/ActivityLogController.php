<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityLogRequest;
use App\Repositories\ActivityLogRepository;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly ActivityLogRepository $activityLogRepository,
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

    private function getUserRole(): string
    {
        $role = auth()->user()->role;
        return $role instanceof \App\Enums\Role ? $role->value : (string) $role;
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $filters = $request->only(['team_member_id', 'feature_id', 'status', 'date_from', 'date_to', 'ai_used']);

        if (!$this->isManager()) {
            $filters['team_member_id'] = auth()->id();
        }

        $logs = $this->activityLogRepository->paginate($filters, $request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json($logs);
        }

        $features = DB::table('features')
            ->whereNull('deleted_at')
            ->orderBy('title')
            ->select('id', 'title')
            ->get();

        $members = $this->isManager()
            ? DB::table('team_members')->where('is_active', true)->orderBy('name')->select('id', 'name')->get()
            : collect();

        return Inertia::render('ActivityLogs/Index', [
            'logs'        => $logs,
            'filters'     => $filters,
            'features'    => $features,
            'teamMembers' => $members,
            'isManager'   => $this->isManager(),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $features = DB::table('features')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['released', 'rejected'])
            ->orderBy('title')
            ->select('id', 'title')
            ->get();

        $aiTools = DB::table('ai_tools')
            ->where('is_active', true)
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        // Pre-fill from yesterday if requested
        $prefill = [];
        if ($request->boolean('prefill_yesterday')) {
            $prefill = $this->activityLogService->prefillFromYesterday(auth()->id());
        }

        $role = $this->getUserRole();

        return Inertia::render('ActivityLogs/Create', [
            'features'    => $features,
            'aiTools'     => $aiTools,
            'prefill'     => $prefill,
            'userRole'    => $role,
        ]);
    }

    public function storeWeb(StoreActivityLogRequest $request)
    {
        $data = $request->validated();
        $data['team_member_id'] = auth()->id();

        $id = $this->activityLogService->create($data);

        return redirect()->route('activity-logs.index')
            ->with('success', 'Activity logged successfully.');
    }

    public function store(StoreActivityLogRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['team_member_id'] = auth()->id();

        $id = $this->activityLogService->create($data);

        return response()->json($this->activityLogRepository->findById($id), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        // Only the owner or a manager can update an activity log
        $log = $this->activityLogRepository->findById($id);
        abort_unless(
            $log->team_member_id === auth()->id() || $this->isManager(),
            403,
            'You can only edit your own activity logs.'
        );

        $this->activityLogRepository->update($id, $request->all());

        return response()->json($this->activityLogRepository->findById($id));
    }

    public function destroy(int $id): JsonResponse
    {
        // Only the owner or a manager can delete an activity log
        $log = $this->activityLogRepository->findById($id);
        abort_unless(
            $log->team_member_id === auth()->id() || $this->isManager(),
            403,
            'You can only delete your own activity logs.'
        );

        $this->activityLogRepository->delete($id);

        return response()->json(['message' => 'Activity log deleted.']);
    }
}
