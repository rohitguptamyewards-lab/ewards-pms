<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\WorkJournalRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class WorkJournalController extends Controller
{
    public function __construct(
        private readonly WorkJournalRepository $journalRepository,
    ) {}

    /**
     * List journal entries for the current user.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $entries = $this->journalRepository->findAll(
            auth()->id(),
            $request->only(['mood', 'from', 'to']),
        );

        $streak = $this->journalRepository->currentStreak(auth()->id());

        if ($request->wantsJson()) {
            return response()->json(['entries' => $entries, 'streak' => $streak]);
        }

        return Inertia::render('Journal/Index', [
            'entries' => $entries,
            'streak'  => $streak,
            'filters' => $request->only(['mood', 'from', 'to']),
        ]);
    }

    /**
     * Show create/edit form for today's entry.
     */
    public function create(): InertiaResponse
    {
        $today = now()->format('Y-m-d');
        $existing = $this->journalRepository->findByDate(auth()->id(), $today);

        return Inertia::render('Journal/Create', [
            'existing' => $existing,
            'today'    => $today,
        ]);
    }

    /**
     * Store or update today's journal entry.
     */
    public function storeWeb(Request $request)
    {
        $data = $request->validate([
            'entry_date'         => 'required|date',
            'accomplishments'    => 'required|string',
            'blockers'           => 'nullable|string',
            'plan_for_tomorrow'  => 'nullable|string',
            'reflections'        => 'nullable|string',
            'mood'               => 'nullable|string|in:great,good,neutral,struggling,blocked',
            'tags'               => 'nullable|array',
            'is_private'         => 'boolean',
        ]);

        $data['team_member_id'] = auth()->id();

        $existing = $this->journalRepository->findByDate(auth()->id(), $data['entry_date']);

        if (!empty($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        if ($existing) {
            $this->journalRepository->update($existing->id, $data);
            $id = $existing->id;
        } else {
            $id = $this->journalRepository->create($data);
        }

        return redirect('/journal')->with('success', 'Journal entry saved.');
    }

    /**
     * Show a single entry.
     */
    public function showPage(int $id): InertiaResponse
    {
        $entry = $this->journalRepository->findById($id);
        abort_if(!$entry, 404);

        // Only allow own entries or managers
        $isOwn = $entry->team_member_id === auth()->id();
        $role  = auth()->user()->role;
        $roleValue = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        $isManager = in_array($roleValue, ['cto', 'ceo', 'manager']);

        abort_if(!$isOwn && !$isManager, 403);
        abort_if(!$isOwn && $entry->is_private, 403);

        return Inertia::render('Journal/Show', [
            'entry' => $entry,
        ]);
    }

    /**
     * API: store entry.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entry_date'         => 'required|date',
            'accomplishments'    => 'required|string',
            'blockers'           => 'nullable|string',
            'plan_for_tomorrow'  => 'nullable|string',
            'reflections'        => 'nullable|string',
            'mood'               => 'nullable|string|in:great,good,neutral,struggling,blocked',
            'tags'               => 'nullable|array',
            'is_private'         => 'boolean',
        ]);

        $data['team_member_id'] = auth()->id();
        if (!empty($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        $id = $this->journalRepository->create($data);

        return response()->json($this->journalRepository->findById($id), 201);
    }

    /**
     * Team journal view (managers only).
     */
    public function team(Request $request): InertiaResponse
    {
        $role = auth()->user()->role;
        $roleValue = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        abort_unless(in_array($roleValue, ['cto', 'ceo', 'manager']), 403);

        $date = $request->input('date', now()->format('Y-m-d'));
        $memberIds = DB::table('team_members')
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $entries = $this->journalRepository->findTeamEntries($memberIds, $date);

        // Who hasn't submitted
        $submittedIds = array_column($entries, 'team_member_id');
        $missing = DB::table('team_members')
            ->where('is_active', true)
            ->whereNotIn('id', $submittedIds)
            ->select('id', 'name', 'role')
            ->orderBy('name')
            ->get();

        return Inertia::render('Journal/Team', [
            'entries'  => $entries,
            'missing'  => $missing,
            'date'     => $date,
        ]);
    }
}
