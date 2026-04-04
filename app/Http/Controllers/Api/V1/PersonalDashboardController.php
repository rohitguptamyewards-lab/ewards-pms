<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PersonalDashboardController extends Controller
{
    /**
     * Personal history dashboard — role-specific view.
     */
    public function history(Request $request): InertiaResponse
    {
        $user   = auth()->user();
        $userId = $user->id;
        $role   = $user->role instanceof \App\Enums\Role ? $user->role->value : (string) $user->role;
        $period = $request->input('period', '30');
        $from   = now()->subDays((int) $period)->format('Y-m-d');

        $data = ['period' => $period, 'role' => $role];

        if (in_array($role, ['developer'])) {
            $data += $this->developerHistory($userId, $from);
        } elseif ($role === 'tester') {
            $data += $this->testerHistory($userId, $from);
        } elseif ($role === 'analyst') {
            $data += $this->analystHistory($userId, $from);
        } else {
            // Managers see team overview
            $data += $this->managerOverview($from);
        }

        return Inertia::render('Personal/History', $data);
    }

    private function developerHistory(int $userId, string $from): array
    {
        $tasksCompleted = DB::table('tasks')
            ->where('assigned_to', $userId)
            ->where('status', 'done')
            ->where('updated_at', '>=', $from)
            ->whereNull('deleted_at')
            ->count();

        $hoursLogged = DB::table('work_logs')
            ->where('user_id', $userId)
            ->where('log_date', '>=', $from)
            ->whereNull('deleted_at')
            ->sum('hours_spent');

        $featuresWorked = DB::table('features')
            ->where('assigned_to', $userId)
            ->where('updated_at', '>=', $from)
            ->whereNull('deleted_at')
            ->count();

        $statusBreakdown = DB::table('tasks')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('assigned_to', $userId)
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get()
            ->toArray();

        $recentTasks = DB::table('tasks')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->select('tasks.id', 'tasks.title', 'tasks.status', 'tasks.updated_at', 'projects.name as project_name')
            ->where('tasks.assigned_to', $userId)
            ->whereNull('tasks.deleted_at')
            ->orderByDesc('tasks.updated_at')
            ->limit(10)
            ->get()
            ->toArray();

        return compact('tasksCompleted', 'hoursLogged', 'featuresWorked', 'statusBreakdown', 'recentTasks');
    }

    private function testerHistory(int $userId, string $from): array
    {
        // Count bug features where this user is QA owner
        $bugsReported = DB::table('features')
            ->where('qa_owner_id', $userId)
            ->where('type', 'bug_fix')
            ->where('created_at', '>=', $from)
            ->whereNull('deleted_at')
            ->count();

        $featuresQAd = DB::table('features')
            ->where('qa_owner_id', $userId)
            ->where('updated_at', '>=', $from)
            ->whereNull('deleted_at')
            ->count();

        $hoursLogged = DB::table('work_logs')
            ->where('user_id', $userId)
            ->where('log_date', '>=', $from)
            ->whereNull('deleted_at')
            ->sum('hours_spent');

        $recentBugs = DB::table('features')
            ->select('id', 'title', 'status', 'priority', 'created_at')
            ->where('qa_owner_id', $userId)
            ->where('type', 'bug_fix')
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->toArray();

        return compact('bugsReported', 'featuresQAd', 'hoursLogged', 'recentBugs');
    }

    private function analystHistory(int $userId, string $from): array
    {
        $requestsCreated = DB::table('requests')
            ->where('requested_by', $userId)
            ->where('created_at', '>=', $from)
            ->whereNull('deleted_at')
            ->count();

        $requestsLinked = DB::table('requests')
            ->where('requested_by', $userId)
            ->where('status', 'linked')
            ->whereNull('deleted_at')
            ->count();

        $hoursLogged = DB::table('work_logs')
            ->where('user_id', $userId)
            ->where('log_date', '>=', $from)
            ->whereNull('deleted_at')
            ->sum('hours_spent');

        $recentRequests = DB::table('requests')
            ->leftJoin('merchants', 'requests.merchant_id', '=', 'merchants.id')
            ->select('requests.id', 'requests.title', 'requests.status', 'requests.demand_count', 'merchants.name as merchant_name')
            ->where('requests.requested_by', $userId)
            ->whereNull('requests.deleted_at')
            ->orderByDesc('requests.created_at')
            ->limit(10)
            ->get()
            ->toArray();

        return compact('requestsCreated', 'requestsLinked', 'hoursLogged', 'recentRequests');
    }

    private function managerOverview(string $from): array
    {
        $teamSize = DB::table('team_members')->where('is_active', true)->count();

        $tasksCompletedTeam = DB::table('tasks')
            ->where('status', 'done')
            ->where('updated_at', '>=', $from)
            ->whereNull('deleted_at')
            ->count();

        $totalHours = DB::table('work_logs')
            ->where('log_date', '>=', $from)
            ->whereNull('deleted_at')
            ->sum('hours_spent');

        $topContributors = DB::table('work_logs')
            ->join('team_members', 'work_logs.user_id', '=', 'team_members.id')
            ->select('team_members.name', DB::raw('SUM(work_logs.hours_spent) as total_hours'))
            ->where('work_logs.log_date', '>=', $from)
            ->whereNull('work_logs.deleted_at')
            ->groupBy('team_members.id', 'team_members.name')
            ->orderByDesc('total_hours')
            ->limit(10)
            ->get()
            ->toArray();

        return compact('teamSize', 'tasksCompletedTeam', 'totalHours', 'topContributors');
    }

    /**
     * Review prep auto-generator.
     */
    public function reviewPrep(Request $request): InertiaResponse
    {
        $userId = auth()->id();
        $period = $request->input('period', '90');
        $from   = now()->subDays((int) $period)->format('Y-m-d');

        $tasksCompleted = DB::table('tasks')
            ->where('assigned_to', $userId)->where('status', 'done')
            ->where('updated_at', '>=', $from)->whereNull('deleted_at')
            ->count();

        $totalHours = DB::table('work_logs')
            ->where('user_id', $userId)->where('log_date', '>=', $from)
            ->whereNull('deleted_at')->sum('hours_spent');

        $featuresDelivered = DB::table('features')
            ->where('assigned_to', $userId)->whereIn('status', ['released', 'ready_for_release'])
            ->where('updated_at', '>=', $from)->whereNull('deleted_at')
            ->select('id', 'title', 'status')
            ->get()->toArray();

        $journalHighlights = DB::table('work_journals')
            ->where('team_member_id', $userId)->where('entry_date', '>=', $from)
            ->whereNull('deleted_at')
            ->select('entry_date', 'accomplishments', 'mood')
            ->orderByDesc('entry_date')
            ->limit(30)
            ->get()->toArray();

        $moodTrend = DB::table('work_journals')
            ->select('mood', DB::raw('COUNT(*) as count'))
            ->where('team_member_id', $userId)->where('entry_date', '>=', $from)
            ->whereNull('deleted_at')->whereNotNull('mood')
            ->groupBy('mood')->get()->toArray();

        return Inertia::render('Personal/ReviewPrep', [
            'period'            => $period,
            'tasksCompleted'    => $tasksCompleted,
            'totalHours'        => $totalHours,
            'featuresDelivered' => $featuresDelivered,
            'journalHighlights' => $journalHighlights,
            'moodTrend'         => $moodTrend,
        ]);
    }

    /**
     * Public team member profile.
     */
    public function profile(int $id): InertiaResponse
    {
        $member = DB::table('team_members')
            ->where('id', $id)
            ->where('is_active', true)
            ->first();

        abort_if(!$member, 404);

        $recentTasks = DB::table('tasks')
            ->select('id', 'title', 'status', 'updated_at')
            ->where('assigned_to', $id)
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()->toArray();

        $hoursThisMonth = DB::table('work_logs')
            ->where('user_id', $id)
            ->where('log_date', '>=', now()->startOfMonth()->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->sum('hours_spent');

        $featuresCount = DB::table('features')
            ->where('assigned_to', $id)
            ->whereNull('deleted_at')
            ->count();

        return Inertia::render('Personal/Profile', [
            'member'         => $member,
            'recentTasks'    => $recentTasks,
            'hoursThisMonth' => $hoursThisMonth,
            'featuresCount'  => $featuresCount,
        ]);
    }
}
