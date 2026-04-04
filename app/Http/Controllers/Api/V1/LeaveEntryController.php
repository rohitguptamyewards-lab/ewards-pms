<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveEntryRequest;
use App\Repositories\LeaveEntryRepository;
use App\Services\LeaveEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LeaveEntryController extends Controller
{
    public function __construct(
        private readonly LeaveEntryService $leaveEntryService,
        private readonly LeaveEntryRepository $leaveEntryRepository,
    ) {}

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $leaves = $this->leaveEntryRepository->findAll(
            $request->only(['team_member_id', 'leave_type', 'month']),
            $request->integer('per_page', 30),
        );

        if ($request->wantsJson()) {
            return response()->json($leaves);
        }

        $teamMembers = DB::table('team_members')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Capacity/LeaveCalendar', [
            'leaves'      => $leaves,
            'teamMembers' => $teamMembers,
            'isManager'   => $this->isManager(),
            'filters'     => $request->only(['team_member_id', 'leave_type', 'month']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $teamMembers = DB::table('team_members')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Capacity/LeaveCreate', [
            'teamMembers' => $teamMembers,
        ]);
    }

    public function storeWeb(StoreLeaveEntryRequest $request)
    {
        $data = $request->validated();
        $this->leaveEntryService->create($data);

        return redirect()->route('leave-entries.index')->with('success', 'Leave recorded.');
    }

    public function store(StoreLeaveEntryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $id = $this->leaveEntryService->create($data);

        return response()->json($this->leaveEntryRepository->findById($id), 201);
    }

    public function update(int $id, StoreLeaveEntryRequest $request): JsonResponse
    {
        $this->leaveEntryService->update($id, $request->validated());

        return response()->json($this->leaveEntryRepository->findById($id));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->leaveEntryService->delete($id);

        return response()->json(['message' => 'Deleted']);
    }
}
