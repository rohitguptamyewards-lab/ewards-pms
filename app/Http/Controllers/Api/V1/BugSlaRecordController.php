<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBugSlaRecordRequest;
use App\Repositories\BugSlaRecordRepository;
use App\Services\BugSlaService;
use App\Services\EmailNotificationService;
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
        private readonly EmailNotificationService $emailService,
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
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

    private function isDevTest(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager', 'developer', 'tester']);
    }

    public function create(): InertiaResponse
    {
        abort_unless($this->isDevTest(), 403, 'Only development team can create bug SLA records.');

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
        abort_unless($this->isDevTest(), 403, 'Only development team can create bug SLA records.');

        $data = $request->validated();
        $this->bugSlaService->create($data);

        try {
            $this->emailService->onBugSlaCreated($data['feature_id'], $data['severity']);
        } catch (\Throwable $e) {
            // Never let email failure break the action
        }

        return redirect()->route('bug-sla.index')
            ->with('success', 'Bug SLA record created.');
    }

    public function store(StoreBugSlaRecordRequest $request): JsonResponse
    {
        abort_unless($this->isDevTest(), 403, 'Only development team can create bug SLA records.');

        $id = $this->bugSlaService->create($request->validated());

        return response()->json($this->bugSlaRepo->findById($id), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($this->isManager(), 403, 'Only managers can update bug SLA records.');

        $this->bugSlaRepo->update($id, $request->all());

        return response()->json($this->bugSlaRepo->findById($id));
    }

    public function reopen(int $id): JsonResponse
    {
        abort_unless($this->isManager(), 403, 'Only managers can reopen bug SLA records.');

        $this->bugSlaService->reopen($id);

        return response()->json(['message' => 'Bug SLA reopened.']);
    }
}
