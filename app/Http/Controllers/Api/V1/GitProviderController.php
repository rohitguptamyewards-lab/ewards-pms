<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGitProviderRequest;
use App\Repositories\GitProviderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GitProviderController extends Controller
{
    public function __construct(
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
        $providers = $this->gitProviderRepository->paginate(
            $request->only(['provider_type', 'is_active']),
            $request->integer('per_page', 15),
        );

        if ($request->wantsJson()) {
            return response()->json($providers);
        }

        return Inertia::render('Git/Providers', [
            'providers' => $providers,
            'isManager' => $this->isManager(),
        ]);
    }

    public function create(): InertiaResponse
    {
        abort_unless($this->isManager(), 403);

        return Inertia::render('Git/ProviderCreate');
    }

    public function storeWeb(StoreGitProviderRequest $request)
    {
        abort_unless($this->isManager(), 403);

        $data = $request->validated();
        if (!empty($data['credentials'])) {
            $data['credentials'] = Crypt::encryptString($data['credentials']);
        }

        $this->gitProviderRepository->create($data);

        return redirect()->route('git-providers.index')
            ->with('success', 'Git provider created.');
    }

    public function store(StoreGitProviderRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (!empty($data['credentials'])) {
            $data['credentials'] = Crypt::encryptString($data['credentials']);
        }

        $id = $this->gitProviderRepository->create($data);

        return response()->json($this->gitProviderRepository->findById($id), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        if (!empty($data['credentials'])) {
            $data['credentials'] = Crypt::encryptString($data['credentials']);
        }

        $this->gitProviderRepository->update($id, $data);

        return response()->json($this->gitProviderRepository->findById($id));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->gitProviderRepository->delete($id);

        return response()->json(['message' => 'Git provider deleted.']);
    }
}
