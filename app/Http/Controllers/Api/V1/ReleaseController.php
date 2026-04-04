<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReleaseRequest;
use App\Repositories\ReleaseRepository;
use App\Services\ReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReleaseController extends Controller
{
    public function __construct(
        private readonly ReleaseService $releaseService,
        private readonly ReleaseRepository $releaseRepository,
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $releases = $this->releaseRepository->paginate(
            $request->only(['environment']),
            $request->integer('per_page', 15),
        );

        if ($request->wantsJson()) {
            return response()->json($releases);
        }

        return Inertia::render('Releases/Index', [
            'releases'  => $releases,
            'isManager' => $this->isManager(),
            'filters'   => $request->only(['environment']),
        ]);
    }

    public function create(): InertiaResponse
    {
        abort_unless($this->isManager(), 403);

        $features = DB::table('features')
            ->whereNull('features.deleted_at')
            ->whereIn('features.status', ['ready_for_release', 'in_qa'])
            ->leftJoin('modules', 'features.module_id', '=', 'modules.id')
            ->select('features.id', 'features.title', 'features.status', 'modules.name as module_name')
            ->orderBy('features.title')
            ->get();

        return Inertia::render('Releases/Create', [
            'features' => $features,
        ]);
    }

    public function storeWeb(StoreReleaseRequest $request)
    {
        abort_unless($this->isManager(), 403);

        $data = $request->validated();
        $featureIds = $data['feature_ids'] ?? [];
        unset($data['feature_ids']);
        $data['deployed_by'] = auth()->id();

        $id = $this->releaseService->create($data, $featureIds);

        return redirect()->route('releases.show', $id)->with('success', 'Release created.');
    }

    public function showPage(int $id): InertiaResponse
    {
        $release = $this->releaseRepository->findById($id);
        abort_if(!$release, 404);

        return Inertia::render('Releases/Show', [
            'release'   => $release,
            'isManager' => $this->isManager(),
        ]);
    }

    public function store(StoreReleaseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $featureIds = $data['feature_ids'] ?? [];
        unset($data['feature_ids']);
        $data['deployed_by'] = auth()->id();

        $id = $this->releaseService->create($data, $featureIds);

        return response()->json($this->releaseRepository->findById($id), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->releaseRepository->findById($id));
    }
}
