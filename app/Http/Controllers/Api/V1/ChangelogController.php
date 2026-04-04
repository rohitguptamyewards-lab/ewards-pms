<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChangelogRequest;
use App\Repositories\ChangelogRepository;
use App\Services\ChangelogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ChangelogController extends Controller
{
    public function __construct(
        private readonly ChangelogService $changelogService,
        private readonly ChangelogRepository $changelogRepository,
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $changelogs = $this->changelogRepository->findAll(
            $request->only(['status']),
            $request->integer('per_page', 15),
        );

        if ($request->wantsJson()) {
            return response()->json($changelogs);
        }

        return Inertia::render('Releases/Changelog', [
            'changelogs' => $changelogs,
            'isManager'  => $this->isManager(),
            'filters'    => $request->only(['status']),
        ]);
    }

    public function create(): InertiaResponse
    {
        abort_unless($this->isManager(), 403);

        $releases = DB::table('releases')
            ->whereNull('deleted_at')
            ->orderByDesc('release_date')
            ->select('id', 'version', 'release_date')
            ->limit(20)
            ->get();

        $modules = DB::table('modules')
            ->whereNull('deleted_at')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Releases/ChangelogCreate', [
            'releases' => $releases,
            'modules'  => $modules,
        ]);
    }

    public function storeWeb(StoreChangelogRequest $request)
    {
        abort_unless($this->isManager(), 403);

        $data = $request->validated();
        $data['drafted_by'] = auth()->id();

        $id = $this->changelogService->create($data);

        return redirect()->route('changelogs.index')->with('success', 'Changelog created.');
    }

    public function store(StoreChangelogRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['drafted_by'] = auth()->id();

        $id = $this->changelogService->create($data);

        return response()->json($this->changelogRepository->findById($id), 201);
    }

    public function approve(int $id): JsonResponse
    {
        abort_unless($this->isManager(), 403);

        $this->changelogService->approve($id, auth()->id());

        return response()->json($this->changelogRepository->findById($id));
    }

    public function publish(int $id): JsonResponse
    {
        abort_unless($this->isManager(), 403);

        $this->changelogService->publish($id);

        return response()->json($this->changelogRepository->findById($id));
    }

    public function published(): JsonResponse
    {
        return response()->json($this->changelogRepository->findPublished());
    }
}
