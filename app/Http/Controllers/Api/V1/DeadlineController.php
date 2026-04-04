<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeadlineRequest;
use App\Repositories\DeadlineRepository;
use App\Services\DeadlineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DeadlineController extends Controller
{
    public function __construct(
        private readonly DeadlineService $deadlineService,
        private readonly DeadlineRepository $deadlineRepository,
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager', 'mc_team']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $filters = $request->only(['state', 'type', 'deadlineable_type', 'due_from', 'due_to']);
        $deadlines = $this->deadlineRepository->paginate($filters, $request->integer('per_page', 15));

        // Enrich with entity names
        foreach ($deadlines->items() as $d) {
            if ($d->deadlineable_type === 'feature') {
                $feature = DB::table('features')->where('id', $d->deadlineable_id)->select('title')->first();
                $d->entity_name = $feature?->title ?? 'Unknown Feature';
            } elseif ($d->deadlineable_type === 'initiative') {
                $initiative = DB::table('initiatives')->where('id', $d->deadlineable_id)->select('title')->first();
                $d->entity_name = $initiative?->title ?? 'Unknown Initiative';
            } else {
                $d->entity_name = 'Unknown';
            }
        }

        if ($request->wantsJson()) {
            return response()->json($deadlines);
        }

        return Inertia::render('Deadlines/Index', [
            'deadlines' => $deadlines,
            'filters'   => $filters,
            'isManager' => $this->isManager(),
        ]);
    }

    public function create(): InertiaResponse
    {
        abort_unless($this->isManager(), 403);

        $features = DB::table('features')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['released', 'rejected'])
            ->orderBy('title')
            ->select('id', 'title')
            ->get();

        $initiatives = DB::table('initiatives')
            ->whereNull('deleted_at')
            ->orderBy('title')
            ->select('id', 'title')
            ->get();

        return Inertia::render('Deadlines/Create', [
            'features'    => $features,
            'initiatives' => $initiatives,
        ]);
    }

    public function storeWeb(StoreDeadlineRequest $request)
    {
        abort_unless($this->isManager(), 403);

        $this->deadlineService->create($request->validated());

        return redirect()->route('deadlines.index')
            ->with('success', 'Deadline created.');
    }

    public function store(StoreDeadlineRequest $request): JsonResponse
    {
        $id = $this->deadlineService->create($request->validated());

        return response()->json($this->deadlineRepository->findById($id), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        // If due_date changed, cascade
        if ($request->has('due_date')) {
            $this->deadlineService->cascadeDeadlineChange($id, $request->input('due_date'));
        } else {
            $this->deadlineRepository->update($id, $request->all());
        }

        return response()->json($this->deadlineRepository->findById($id));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deadlineRepository->delete($id);

        return response()->json(['message' => 'Deadline deleted.']);
    }
}
