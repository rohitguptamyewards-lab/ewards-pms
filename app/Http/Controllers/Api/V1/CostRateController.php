<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCostRateRequest;
use App\Repositories\CostRateRepository;
use App\Services\CostRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CostRateController extends Controller
{
    public function __construct(
        private readonly CostRateService $costRateService,
        private readonly CostRateRepository $costRateRepository,
    ) {}

    private function isCto(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return $value === 'cto';
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        abort_unless($this->isCto(), 403);

        $rates = $this->costRateRepository->findAll(
            $request->only(['team_member_id']),
            $request->integer('per_page', 20),
        );

        if ($request->wantsJson()) {
            return response()->json($rates);
        }

        $teamMembers = DB::table('team_members')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('CostIntelligence/CostRates', [
            'rates'       => $rates,
            'teamMembers' => $teamMembers,
            'filters'     => $request->only(['team_member_id']),
        ]);
    }

    public function create(): InertiaResponse
    {
        abort_unless($this->isCto(), 403);

        $teamMembers = DB::table('team_members')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('CostIntelligence/CostRateCreate', [
            'teamMembers' => $teamMembers,
        ]);
    }

    public function storeWeb(StoreCostRateRequest $request)
    {
        abort_unless($this->isCto(), 403);

        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $id = $this->costRateService->create($data);

        return redirect()->route('cost-rates.index')->with('success', 'Cost rate created.');
    }

    public function store(StoreCostRateRequest $request): JsonResponse
    {
        abort_unless($this->isCto(), 403);

        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $id = $this->costRateService->create($data);

        return response()->json($this->costRateRepository->findById($id), 201);
    }
}
