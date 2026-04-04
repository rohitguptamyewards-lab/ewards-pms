<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class OnboardingController extends Controller
{
    private function assertManager(): void
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        abort_unless(in_array($value, ['cto', 'ceo', 'manager']), 403);
    }

    /**
     * Onboarding dashboard — list members being onboarded.
     */
    public function index(): InertiaResponse
    {
        $this->assertManager();

        $members = DB::table('team_members')
            ->whereNotNull('onboarding_status')
            ->where('onboarding_status', '!=', 'completed')
            ->where('is_active', true)
            ->orderBy('join_date')
            ->get();

        $completed = DB::table('team_members')
            ->where('onboarding_status', 'completed')
            ->where('is_active', true)
            ->orderByDesc('join_date')
            ->limit(10)
            ->get();

        $buddies = DB::table('team_members')
            ->where('is_active', true)
            ->select('id', 'name', 'role')
            ->orderBy('name')
            ->get();

        return Inertia::render('Onboarding/Index', [
            'activeOnboarding'    => $members,
            'completedOnboarding' => $completed,
            'buddies'             => $buddies,
        ]);
    }

    /**
     * Start onboarding for a team member.
     */
    public function start(Request $request, int $id)
    {
        $this->assertManager();

        $data = $request->validate([
            'buddy_id'           => 'nullable|integer|exists:team_members,id',
            'onboarding_due_date'=> 'nullable|date',
            'onboarding_checklist' => 'nullable|array',
        ]);

        $defaultChecklist = [
            ['task' => 'Welcome meeting with manager', 'done' => false],
            ['task' => 'Set up development environment', 'done' => false],
            ['task' => 'Read project documentation', 'done' => false],
            ['task' => 'Meet buddy for codebase walkthrough', 'done' => false],
            ['task' => 'Complete first task assignment', 'done' => false],
            ['task' => 'Attend team standup (first week)', 'done' => false],
            ['task' => '30-day ramp-up review', 'done' => false],
        ];

        DB::table('team_members')->where('id', $id)->update([
            'onboarding_status'    => 'in_progress',
            'buddy_id'             => $data['buddy_id'] ?? null,
            'onboarding_due_date'  => $data['onboarding_due_date'] ?? now()->addDays(30)->format('Y-m-d'),
            'onboarding_checklist' => json_encode($data['onboarding_checklist'] ?? $defaultChecklist),
            'updated_at'           => now(),
        ]);

        return redirect('/onboarding')->with('success', 'Onboarding started.');
    }

    /**
     * Update onboarding checklist progress.
     */
    public function updateChecklist(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'onboarding_checklist' => 'required|array',
        ]);

        // Check if all items are done
        $allDone = collect($data['onboarding_checklist'])->every(fn ($item) => $item['done'] ?? false);

        DB::table('team_members')->where('id', $id)->update([
            'onboarding_checklist' => json_encode($data['onboarding_checklist']),
            'onboarding_status'    => $allDone ? 'completed' : 'in_progress',
            'updated_at'           => now(),
        ]);

        return response()->json(['message' => 'Checklist updated.', 'completed' => $allDone]);
    }

    /**
     * Offboarding dashboard.
     */
    public function offboardingIndex(): InertiaResponse
    {
        $this->assertManager();

        $members = DB::table('team_members')
            ->whereNotNull('exit_date')
            ->orderBy('exit_date')
            ->get();

        return Inertia::render('Offboarding/Index', [
            'members' => $members,
        ]);
    }

    /**
     * Start offboarding for a team member.
     */
    public function startOffboarding(Request $request, int $id)
    {
        $this->assertManager();

        $data = $request->validate([
            'exit_date'  => 'required|date',
            'exit_notes' => 'nullable|string',
        ]);

        $defaultChecklist = [
            ['task' => 'Knowledge transfer sessions scheduled', 'done' => false],
            ['task' => 'Document all owned processes', 'done' => false],
            ['task' => 'Reassign active tasks', 'done' => false],
            ['task' => 'Reassign feature ownership', 'done' => false],
            ['task' => 'Revoke system access', 'done' => false],
            ['task' => 'Exit interview completed', 'done' => false],
            ['task' => 'Final handover review', 'done' => false],
        ];

        DB::table('team_members')->where('id', $id)->update([
            'exit_date'             => $data['exit_date'],
            'exit_notes'            => $data['exit_notes'] ?? null,
            'offboarding_status'    => 'in_progress',
            'offboarding_checklist' => json_encode($defaultChecklist),
            'updated_at'            => now(),
        ]);

        return redirect('/offboarding')->with('success', 'Offboarding started.');
    }

    /**
     * Update offboarding checklist.
     */
    public function updateOffboardingChecklist(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'offboarding_checklist' => 'required|array',
        ]);

        $allDone = collect($data['offboarding_checklist'])->every(fn ($item) => $item['done'] ?? false);

        DB::table('team_members')->where('id', $id)->update([
            'offboarding_checklist' => json_encode($data['offboarding_checklist']),
            'offboarding_status'    => $allDone ? 'completed' : 'in_progress',
            'updated_at'            => now(),
        ]);

        return response()->json(['message' => 'Checklist updated.', 'completed' => $allDone]);
    }
}
