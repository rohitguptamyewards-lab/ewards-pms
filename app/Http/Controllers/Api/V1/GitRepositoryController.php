<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGitRepositoryRequest;
use App\Repositories\GitProviderRepository;
use App\Repositories\GitRepositoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GitRepositoryController extends Controller
{
    public function __construct(
        private readonly GitRepositoryRepository $gitRepoRepository,
        private readonly GitProviderRepository $gitProviderRepository,
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $repos = $this->gitRepoRepository->paginate(
            $request->only(['git_provider_id', 'is_active']),
            $request->integer('per_page', 15),
        );

        if ($request->wantsJson()) {
            return response()->json($repos);
        }

        $providers = $this->gitProviderRepository->findAll();

        return Inertia::render('Git/Repositories', [
            'repositories' => $repos,
            'providers'    => $providers,
            'isManager'    => $this->isManager(),
        ]);
    }

    public function create(): InertiaResponse
    {
        abort_unless($this->isManager(), 403);

        $providers = $this->gitProviderRepository->findAll();

        return Inertia::render('Git/RepositoryCreate', [
            'providers' => $providers,
        ]);
    }

    public function storeWeb(StoreGitRepositoryRequest $request)
    {
        abort_unless($this->isManager(), 403);

        $data = $request->validated();
        if (!empty($data['webhook_secret'])) {
            $data['webhook_secret'] = Crypt::encryptString($data['webhook_secret']);
        }

        $this->gitRepoRepository->create($data);

        return redirect()->route('git-repositories.index')
            ->with('success', 'Repository created.');
    }

    public function store(StoreGitRepositoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (!empty($data['webhook_secret'])) {
            $data['webhook_secret'] = Crypt::encryptString($data['webhook_secret']);
        }

        $id = $this->gitRepoRepository->create($data);

        return response()->json($this->gitRepoRepository->findById($id), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        if (!empty($data['webhook_secret'])) {
            $data['webhook_secret'] = Crypt::encryptString($data['webhook_secret']);
        }

        $this->gitRepoRepository->update($id, $data);

        return response()->json($this->gitRepoRepository->findById($id));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->gitRepoRepository->delete($id);

        return response()->json(['message' => 'Repository deleted.']);
    }
}
