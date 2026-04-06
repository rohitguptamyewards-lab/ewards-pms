<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMerchantCommunicationRequest;
use App\Repositories\MerchantCommunicationRepository;
use App\Services\MerchantCommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MerchantCommunicationController extends Controller
{
    public function __construct(
        private readonly MerchantCommunicationService $merchantCommService,
        private readonly MerchantCommunicationRepository $merchantCommRepo,
    ) {}

    private function isMcUp(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager', 'mc_team']);
    }

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $filters = $request->only(['merchant_id', 'team_member_id', 'channel', 'commitment_made']);

        if (!$this->isMcUp()) {
            $filters['team_member_id'] = auth()->id();
        }

        $communications = $this->merchantCommRepo->paginate($filters, $request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json($communications);
        }

        $merchants = DB::table('merchants')
            ->where('is_active', true)
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        $features = DB::table('features')
            ->whereNull('deleted_at')
            ->orderBy('title')
            ->select('id', 'title')
            ->get();

        $members = $this->isMcUp()
            ? DB::table('team_members')->where('is_active', true)->orderBy('name')->select('id', 'name')->get()
            : collect();

        $slippedCommitments = $this->isMcUp()
            ? $this->merchantCommService->findSlippedCommitments()
            : [];

        return Inertia::render('MerchantCommunications/Index', [
            'communications'     => $communications,
            'filters'            => $filters,
            'merchants'          => $merchants,
            'features'           => $features,
            'teamMembers'        => $members,
            'slippedCommitments' => $slippedCommitments,
            'isManager'          => $this->isMcUp(),
        ]);
    }

    public function create(): InertiaResponse
    {
        abort_unless($this->isMcUp(), 403, 'Only managers and MC team can log merchant communications.');

        $merchants = DB::table('merchants')
            ->where('is_active', true)
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        $features = DB::table('features')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['released', 'rejected'])
            ->orderBy('title')
            ->select('id', 'title')
            ->get();

        return Inertia::render('MerchantCommunications/Create', [
            'merchants' => $merchants,
            'features'  => $features,
        ]);
    }

    public function storeWeb(StoreMerchantCommunicationRequest $request)
    {
        abort_unless($this->isMcUp(), 403, 'Only managers and MC team can log merchant communications.');

        $data = $request->validated();
        $data['team_member_id'] = auth()->id();

        $this->merchantCommService->create($data);

        return redirect()->route('merchant-communications.index')
            ->with('success', 'Communication logged.');
    }

    public function store(StoreMerchantCommunicationRequest $request): JsonResponse
    {
        abort_unless($this->isMcUp(), 403, 'Only managers and MC team can log merchant communications.');

        $data = $request->validated();
        $data['team_member_id'] = auth()->id();

        $id = $this->merchantCommService->create($data);

        return response()->json($this->merchantCommRepo->findById($id), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($this->isMcUp(), 403, 'Only managers and MC team can update merchant communications.');

        $this->merchantCommRepo->update($id, $request->all());

        return response()->json($this->merchantCommRepo->findById($id));
    }
}
