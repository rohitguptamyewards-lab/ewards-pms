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

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager', 'mc_team', 'sales']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $filters = $request->only(['merchant_id', 'team_member_id', 'channel', 'commitment_made']);

        if (!$this->isManager()) {
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

        $members = $this->isManager()
            ? DB::table('team_members')->where('is_active', true)->orderBy('name')->select('id', 'name')->get()
            : collect();

        $slippedCommitments = $this->isManager()
            ? $this->merchantCommService->findSlippedCommitments()
            : [];

        return Inertia::render('MerchantCommunications/Index', [
            'communications'     => $communications,
            'filters'            => $filters,
            'merchants'          => $merchants,
            'features'           => $features,
            'teamMembers'        => $members,
            'slippedCommitments' => $slippedCommitments,
            'isManager'          => $this->isManager(),
        ]);
    }

    public function create(): InertiaResponse
    {
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
        $data = $request->validated();
        $data['team_member_id'] = auth()->id();

        $this->merchantCommService->create($data);

        return redirect()->route('merchant-communications.index')
            ->with('success', 'Communication logged.');
    }

    public function store(StoreMerchantCommunicationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['team_member_id'] = auth()->id();

        $id = $this->merchantCommService->create($data);

        return response()->json($this->merchantCommRepo->findById($id), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->merchantCommRepo->update($id, $request->all());

        return response()->json($this->merchantCommRepo->findById($id));
    }
}
