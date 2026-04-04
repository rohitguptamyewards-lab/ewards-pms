<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBugSlaRecordRequest;
use App\Repositories\BugSlaRecordRepository;
use App\Services\BugSlaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BugSlaRecordController extends Controller
{
    public function __construct(
        private readonly BugSlaService $bugSlaService,
        private readonly BugSlaRecordRepository $bugSlaRepo,
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager', 'mc_team']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $filters = $request->only(['severity', 'feature_id', 'origin', 'breached']);
        $records = $this->bugSlaRepo->paginate($filters, $request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json($records);
        }

        $features = DB::table('features')
            ->whereNull('deleted_at')
            ->orderBy('title')
            ->select('id', 'title')
            ->get();

        return Inertia::render('BugSla/Index', [
            'records'   => $records,
            'filters'   => $filters,
            'features'  => $features,
            'isManager' => $this->isManager(),
        ]);
    }

    public function create(): InertiaResponse
    {
        $features = DB::table('features')
            ->whereNull('deleted_at')
            ->orderBy('title')
            ->select('id', 'title')
            ->get();

        return Inertia::render('BugSla/Create', [
            'features' => $features,
        ]);
    }

    public function storeWeb(StoreBugSlaRecordRequest $request)
    {
        $this->bugSlaService->create($request->validated());

        return redirect()->route('bug-sla.index')
            ->with('success', 'Bug SLA record created.');
    }

    public function store(StoreBugSlaRecordRequest $request): JsonResponse
    {
        $id = $this->bugSlaService->create($request->validated());

        return response()->json($this->bugSlaRepo->findById($id), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->bugSlaRepo->update($id, $request->all());

        return response()->json($this->bugSlaRepo->findById($id));
    }

    public function reopen(int $id): JsonResponse
    {
        $this->bugSlaService->reopen($id);

        return response()->json(['message' => 'Bug SLA reopened.']);
    }
}
