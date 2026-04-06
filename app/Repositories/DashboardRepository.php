<?php

namespace App\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    /**
     * Get individual dashboard data for a specific user.
     *
     * @param int $userId
     * @return array{todaysLogs: array, myTasks: array, myProjects: array, weeklyHours: float}
     */
    public function getIndividualData(int $userId): array
    {
        $today = Carbon::today()->toDateString();
        $weekAgo = Carbon::today()->subDays(7)->toDateString();

        $todaysLogs = DB::table('work_logs')
            ->leftJoin('projects', 'work_logs.project_id', '=', 'projects.id')
            ->leftJoin('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->select(
                'work_logs.*',
                'projects.name as project_name',
                'tasks.title as task_title'
            )
            ->where('work_logs.user_id', $userId)
            ->where('work_logs.log_date', $today)
            ->whereNull('work_logs.deleted_at')
            ->orderByDesc('work_logs.created_at')
            ->get()
            ->toArray();

        $myTasks = DB::table('tasks')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->select(
                'tasks.*',
                'projects.name as project_name'
            )
            ->where('tasks.assigned_to', $userId)
            ->whereIn('tasks.status', ['open', 'in_progress', 'blocked'])
            ->whereNull('tasks.deleted_at')
            ->orderBy('tasks.priority')
            ->orderByDesc('tasks.created_at')
            ->get()
            ->toArray();

        $myProjects = DB::table('projects')
            ->join('project_members', 'projects.id', '=', 'project_members.project_id')
            ->select(
                'projects.*',
                DB::raw("(SELECT COALESCE(SUM(wl.hours_spent), 0) FROM work_logs wl WHERE wl.project_id = projects.id AND wl.user_id = {$userId} AND wl.deleted_at IS NULL) as my_hours"),
                DB::raw("(SELECT COALESCE(ROUND(SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2), 0) FROM tasks t WHERE t.project_id = projects.id AND t.deleted_at IS NULL) as progress")
            )
            ->where('project_members.user_id', $userId)
            ->where('projects.status', 'active')
            ->whereNull('projects.deleted_at')
            ->get()
            ->toArray();

        $weeklyHoursResult = DB::table('work_logs')
            ->where('user_id', $userId)
            ->where('log_date', '>=', $weekAgo)
            ->whereNull('deleted_at')
            ->sum('hours_spent');

        return [
            'todaysLogs'  => $todaysLogs,
            'myTasks'     => $myTasks,
            'myProjects'  => $myProjects,
            'weeklyHours' => round((float) $weeklyHoursResult, 2),
        ];
    }

    /**
     * Get manager dashboard data: overview of all projects, tasks, team workload.
     *
     * @return array{activeProjects: array, openTasks: int, blockedTasks: array, overdueTasks: array, teamWorkload: array, untriagedRequests: int}
     */
    public function getManagerData(): array
    {
        $today = Carbon::today()->toDateString();

        // Active projects with progress
        $activeProjects = DB::table('projects')
            ->leftJoin('team_members', 'projects.owner_id', '=', 'team_members.id')
            ->select(
                'projects.*',
                'team_members.name as owner_name',
                DB::raw('(SELECT COUNT(*) FROM tasks WHERE tasks.project_id = projects.id AND tasks.deleted_at IS NULL) as task_count'),
                DB::raw('(SELECT COALESCE(ROUND(SUM(CASE WHEN tasks.status = \'done\' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2), 0) FROM tasks WHERE tasks.project_id = projects.id AND tasks.deleted_at IS NULL) as progress')
            )
            ->where('projects.status', 'active')
            ->whereNull('projects.deleted_at')
            ->get()
            ->toArray();

        // Open tasks count
        $openTasks = DB::table('tasks')
            ->where('status', 'open')
            ->whereNull('deleted_at')
            ->count();

        // Blocked tasks with assignee and latest blocker reason from work_logs
        $blockedTasks = DB::table('tasks')
            ->leftJoin('team_members', 'tasks.assigned_to', '=', 'team_members.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->select(
                'tasks.id',
                'tasks.title',
                'team_members.name as assignee_name',
                'projects.name as project_name',
                DB::raw("(SELECT wl.blocker FROM work_logs wl WHERE wl.task_id = tasks.id AND wl.blocker IS NOT NULL AND wl.blocker != '' AND wl.deleted_at IS NULL ORDER BY wl.created_at DESC LIMIT 1) as blocker_reason")
            )
            ->where('tasks.status', 'blocked')
            ->whereNull('tasks.deleted_at')
            ->get()
            ->toArray();

        // Overdue tasks: deadline < today AND status != done
        $overdueTasks = DB::table('tasks')
            ->leftJoin('team_members', 'tasks.assigned_to', '=', 'team_members.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->select(
                'tasks.id',
                'tasks.title',
                'tasks.deadline',
                'tasks.status',
                'team_members.name as assignee_name',
                'projects.name as project_name'
            )
            ->where('tasks.deadline', '<', $today)
            ->where('tasks.status', '!=', 'done')
            ->whereNull('tasks.deleted_at')
            ->get()
            ->toArray();

        // Team workload: today's work_logs grouped by user with project/task/hours
        $teamWorkload = DB::table('work_logs')
            ->join('team_members', 'work_logs.user_id', '=', 'team_members.id')
            ->leftJoin('projects', 'work_logs.project_id', '=', 'projects.id')
            ->leftJoin('tasks', 'work_logs.task_id', '=', 'tasks.id')
            ->select(
                'work_logs.user_id',
                'team_members.name as user_name',
                'projects.name as project_name',
                'tasks.title as task_title',
                'work_logs.hours_spent',
                'work_logs.note'
            )
            ->where('work_logs.log_date', $today)
            ->whereNull('work_logs.deleted_at')
            ->orderBy('team_members.name')
            ->get()
            ->toArray();

        // Untriaged requests count
        $untriagedRequests = DB::table('requests')
            ->where('status', 'received')
            ->whereNull('deleted_at')
            ->count();

        return [
            'activeProjects'    => [
                'count' => count($activeProjects),
                'list'  => $activeProjects,
            ],
            'openTasks'         => $openTasks,
            'blockedTasks'      => $blockedTasks,
            'overdueTasks'      => $overdueTasks,
            'teamWorkload'      => $teamWorkload,
            'untriagedRequests' => $untriagedRequests,
        ];
    }

    /**
     * CEO dashboard: high-level business view — pipeline counts, no task/person details.
     */
    public function getCEOData(): array
    {
        $featurePipelineRows = DB::table('features')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();

        $featurePipeline = [];
        foreach ($featurePipelineRows as $row) {
            $featurePipeline[$row->status] = (int) $row->count;
        }

        $requestPipelineRows = DB::table('requests')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();

        $requestPipeline = [];
        foreach ($requestPipelineRows as $row) {
            $requestPipeline[$row->status] = (int) $row->count;
        }

        $activeProjects = DB::table('projects')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();

        $teamSize = DB::table('team_members')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();

        $totalHoursThisMonth = DB::table('work_logs')
            ->whereNull('deleted_at')
            ->whereYear('log_date', now()->year)
            ->whereMonth('log_date', now()->month)
            ->sum('hours_spent');

        return [
            'featurePipeline'    => $featurePipeline,
            'requestPipeline'    => $requestPipeline,
            'activeProjects'     => $activeProjects,
            'teamSize'           => $teamSize,
            'hoursThisMonth'     => round((float) $totalHoursThisMonth, 1),
        ];
    }

    /**
     * MC Team dashboard: request-centric — untriaged queue + merchant-blocked.
     */
    public function getMCTeamData(): array
    {
        $untriagedRequests = DB::table('requests')
            ->leftJoin('merchants', 'requests.merchant_id', '=', 'merchants.id')
            ->leftJoin('team_members', 'requests.requested_by', '=', 'team_members.id')
            ->select(
                'requests.id',
                'requests.title',
                'requests.type',
                'requests.urgency',
                'requests.demand_count',
                'requests.created_at',
                'merchants.name as merchant_name',
                'team_members.name as requester_name'
            )
            ->where('requests.status', 'received')
            ->whereNull('requests.deleted_at')
            ->orderBy('requests.urgency')
            ->orderBy('requests.created_at')
            ->get()
            ->toArray();

        $merchantBlockedRequests = DB::table('requests')
            ->leftJoin('merchants', 'requests.merchant_id', '=', 'merchants.id')
            ->leftJoin('team_members', 'requests.requested_by', '=', 'team_members.id')
            ->select(
                'requests.id',
                'requests.title',
                'requests.type',
                'requests.status',
                'requests.demand_count',
                'requests.created_at',
                'merchants.name as merchant_name',
                'team_members.name as requester_name'
            )
            ->where('requests.urgency', 'merchant_blocked')
            ->whereNotIn('requests.status', ['rejected', 'fulfilled'])
            ->whereNull('requests.deleted_at')
            ->orderBy('requests.created_at')
            ->get()
            ->toArray();

        $total           = DB::table('requests')->whereNull('deleted_at')->count();
        $untriaged       = DB::table('requests')->where('status', 'received')->whereNull('deleted_at')->count();
        $linked          = DB::table('requests')->where('status', 'linked')->whereNull('deleted_at')->count();
        $merchantBlocked = DB::table('requests')
            ->where('urgency', 'merchant_blocked')
            ->whereNotIn('status', ['rejected', 'fulfilled'])
            ->whereNull('deleted_at')
            ->count();

        return [
            'untriagedRequests'       => $untriagedRequests,
            'merchantBlockedRequests' => $merchantBlockedRequests,
            'stats' => [
                'total'           => $total,
                'untriaged'       => $untriaged,
                'linked'          => $linked,
                'merchantBlocked' => $merchantBlocked,
            ],
        ];
    }

    /**
     * Developer dashboard — enhanced with context-switching, estimation accuracy,
     * personal kanban (sprint features), sprint commitment, onboarding view.
     * Items 44, 45, 47, 48, 49.
     */
    public function getDeveloperData(int $userId): array
    {
        $base = $this->getIndividualData($userId);

        // Item 44 — Context-switching warning: count distinct features worked on this week
        $weekAgo = Carbon::today()->subDays(7)->toDateString();
        $today   = Carbon::today()->toDateString();

        $featuresThisWeek = DB::table('work_logs')
            ->where('user_id', $userId)
            ->whereNotNull('feature_id')
            ->whereBetween('log_date', [$weekAgo, $today])
            ->whereNull('deleted_at')
            ->distinct('feature_id')
            ->count('feature_id');

        $contextSwitchWarning = $featuresThisWeek >= 4
            ? "You've worked on {$featuresThisWeek} different features this week. Context switching may reduce productivity."
            : null;

        // Item 45 — Estimation accuracy feedback
        $estimationAccuracy = DB::table('feature_assignments')
            ->where('team_member_id', $userId)
            ->whereNotNull('actual_hours')
            ->whereNotNull('estimated_hours')
            ->where('estimated_hours', '>', 0)
            ->select(
                DB::raw('AVG(actual_hours / estimated_hours * 100) as avg_accuracy_pct'),
                DB::raw('COUNT(*) as sample_count')
            )
            ->first();

        // Item 47 — Personal kanban: my assigned sprint features by status
        $activeSprint = DB::table('sprints')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->first();

        $sprintKanban = [];
        if ($activeSprint) {
            $sprintKanban = DB::table('sprint_features')
                ->join('features', 'sprint_features.feature_id', '=', 'features.id')
                // sprint_features has no softDeletes
                ->whereNull('features.deleted_at')
                ->where('sprint_features.sprint_id', $activeSprint->id)
                ->where('features.assigned_to', $userId)
                ->select('features.id', 'features.title', 'features.status', 'features.priority', 'features.estimated_hours')
                ->get()
                ->toArray();
        }

        // Item 48 — Sprint commitment view
        $sprintCommitment = null;
        if ($activeSprint) {
            $totalFeatures = DB::table('sprint_features')
                ->join('features', 'sprint_features.feature_id', '=', 'features.id')
                ->where('sprint_features.sprint_id', $activeSprint->id)
                // sprint_features has no softDeletes
                ->whereNull('features.deleted_at')
                ->where('features.assigned_to', $userId)
                ->count();

            $completedFeatures = DB::table('sprint_features')
                ->join('features', 'sprint_features.feature_id', '=', 'features.id')
                ->where('sprint_features.sprint_id', $activeSprint->id)
                // sprint_features has no softDeletes
                ->whereNull('features.deleted_at')
                ->where('features.assigned_to', $userId)
                ->where('features.status', 'released')
                ->count();

            $sprintCommitment = [
                'sprint_id'          => $activeSprint->id,
                'sprint_name'        => $activeSprint->name ?? "Sprint #{$activeSprint->sprint_number}",
                'end_date'           => $activeSprint->end_date,
                'total_committed'    => $totalFeatures,
                'completed'          => $completedFeatures,
                'completion_rate'    => $totalFeatures > 0
                    ? round($completedFeatures / $totalFeatures * 100, 1)
                    : 0,
            ];
        }

        // Item 49 — Onboarding view for new hires (<4 weeks since joining)
        $member = DB::table('team_members')->where('id', $userId)->first();
        $isNewHire = false;
        $onboardingStatus = null;

        if ($member && $member->joining_date) {
            $joiningDate = Carbon::parse($member->joining_date);
            $weeksSinceJoining = $joiningDate->diffInWeeks(now());
            if ($weeksSinceJoining < 4) {
                $isNewHire = true;
                $onboardingRecord = DB::table('team_members')
                    ->where('id', $userId)
                    ->select('onboarding_status', 'onboarding_checklist')
                    ->first();
                $onboardingStatus = $onboardingRecord ? [
                    'weeks_since_joining' => $weeksSinceJoining,
                    'status'              => $onboardingRecord->onboarding_status ?? 'not_started',
                    'checklist'           => json_decode($onboardingRecord->onboarding_checklist ?? '{}', true),
                ] : null;
            }
        }

        return array_merge($base, [
            'features_this_week'    => $featuresThisWeek,
            'context_switch_warning' => $contextSwitchWarning,
            'estimation_accuracy'   => $estimationAccuracy ? [
                'avg_pct'     => round((float) $estimationAccuracy->avg_accuracy_pct, 1),
                'sample_count' => (int) $estimationAccuracy->sample_count,
            ] : null,
            'sprint_kanban'       => $sprintKanban,
            'sprint_commitment'   => $sprintCommitment,
            'is_new_hire'         => $isNewHire,
            'onboarding_status'   => $onboardingStatus,
            'active_sprint'       => $activeSprint ? [
                'id'         => $activeSprint->id,
                'name'       => $activeSprint->name ?? "Sprint #{$activeSprint->sprint_number}",
                'end_date'   => $activeSprint->end_date,
            ] : null,
        ]);
    }

    /**
     * Analyst dashboard — enhanced with spec writing queue, spec quality metrics,
     * documentation coverage, test scenario coverage.
     * Items 54, 55, 56, 57.
     */
    public function getAnalystData(int $userId): array
    {
        $base = $this->getIndividualData($userId);

        // Item 54 — Spec writing queue sorted by dev start urgency
        $specQueue = DB::table('features')
            ->leftJoin('modules', 'features.module_id', '=', 'modules.id')
            ->leftJoin('sprints', function ($join) {
                $join->on('sprints.id', '=', DB::raw(
                    '(SELECT sf.sprint_id FROM sprint_features sf JOIN sprints s ON s.id = sf.sprint_id WHERE sf.feature_id = features.id AND s.status = \'active\' LIMIT 1)'
                ));
            })
            ->whereNull('features.deleted_at')
            ->whereIn('features.status', ['backlog', 'in_progress'])
            ->where(function ($q) {
                $q->whereNull('features.spec_version')
                  ->orWhere('features.spec_version', '');
            })
            ->select(
                'features.id',
                'features.title',
                'features.priority',
                'features.status',
                'features.deadline',
                'features.estimated_hours',
                'modules.name as module_name',
                'sprints.start_date as sprint_start'
            )
            ->orderByRaw("CASE features.priority WHEN 'p0' THEN 1 WHEN 'p1' THEN 2 WHEN 'p2' THEN 3 ELSE 4 END")
            ->orderBy('features.deadline')
            ->limit(20)
            ->get()
            ->toArray();

        // Item 55 — Spec quality metrics (freeze-to-dev ratio, change rate)
        $totalSpecs = DB::table('feature_spec_versions')
            ->whereNull('deleted_at')
            ->count();

        $frozenSpecs = DB::table('feature_spec_versions')
            ->whereNull('deleted_at')
            ->where('state', 'frozen')
            ->count();

        // Features that went to dev after spec freeze
        $frozenThenDev = DB::table('feature_spec_versions')
            ->join('features', 'feature_spec_versions.feature_id', '=', 'features.id')
            ->whereNull('feature_spec_versions.deleted_at')
            ->where('feature_spec_versions.state', 'frozen')
            ->whereIn('features.status', ['in_progress', 'in_review', 'in_qa', 'ready_for_release', 'released'])
            ->count();

        $specQualityMetrics = [
            'total_specs'         => $totalSpecs,
            'frozen_specs'        => $frozenSpecs,
            'freeze_to_dev_ratio' => $frozenSpecs > 0
                ? round($frozenThenDev / $frozenSpecs * 100, 1)
                : 0,
        ];

        // Item 56 — Documentation coverage
        $featuresWithDocs = DB::table('features')
            ->whereNull('deleted_at')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('documents')
                  ->whereColumn('documents.documentable_id', 'features.id')
                  ->where('documents.documentable_type', 'feature');
                  // documents table has no deleted_at (no softDeletes)
            })
            ->count();

        $totalFeatures = DB::table('features')->whereNull('deleted_at')->count();
        $docCoverage = $totalFeatures > 0
            ? round($featuresWithDocs / $totalFeatures * 100, 1)
            : 0;

        // Item 57 — Test scenario coverage (features with spec versions in testing state)
        $featuresWithTests = DB::table('features')
            ->whereNull('deleted_at')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('feature_spec_versions')
                  ->whereColumn('feature_spec_versions.feature_id', 'features.id')
                  ->whereNull('feature_spec_versions.deleted_at')
                  ->where('feature_spec_versions.state', 'frozen');
            })
            ->count();

        $testCoverage = $totalFeatures > 0
            ? round($featuresWithTests / $totalFeatures * 100, 1)
            : 0;

        return array_merge($base, [
            'spec_queue'           => $specQueue,
            'spec_quality_metrics' => $specQualityMetrics,
            'doc_coverage'         => [
                'coverage_pct'        => $docCoverage,
                'features_with_docs'  => $featuresWithDocs,
                'total_features'      => $totalFeatures,
            ],
            'test_coverage' => [
                'coverage_pct'         => $testCoverage,
                'features_with_tests'  => $featuresWithTests,
                'total_features'       => $totalFeatures,
            ],
        ]);
    }

    /**
     * CEO dashboard — enhanced with narrative summary, investment vs impact matrix,
     * merchant tier fulfilment rates, CTO-attributed estimates.
     * Items 39, 40, 41, 43.
     */
    public function getCEODataEnhanced(): array
    {
        $base = $this->getCEOData();

        // Item 39 — Auto-generated narrative summary
        $inProgress = $base['featurePipeline']['in_progress'] ?? 0;
        $released   = $base['featurePipeline']['released'] ?? 0;
        $backlog    = $base['featurePipeline']['backlog'] ?? 0;

        $narrative = "The team currently has {$inProgress} feature(s) actively in development, "
                   . "{$released} released, and {$backlog} in the backlog. "
                   . "{$base['activeProjects']} project(s) are active with {$base['teamSize']} team members. "
                   . "{$base['hoursThisMonth']}h logged this month.";

        // Item 40 — Investment vs impact matrix (features with cost + revenue data)
        try {
            $investmentMatrix = DB::table('features')
                ->whereNull('deleted_at')
                ->whereNotNull('attributed_revenue')
                ->whereNotNull('maintenance_cost_monthly')
                ->select(
                    'id', 'title', 'status', 'priority',
                    'attributed_revenue', 'maintenance_cost_monthly',
                    'estimated_hours',
                    DB::raw('COALESCE(attributed_revenue, 0) - COALESCE(maintenance_cost_monthly, 0) as roi_monthly')
                )
                ->orderByDesc('roi_monthly')
                ->limit(20)
                ->get()
                ->toArray();
        } catch (\Throwable $e) {
            $investmentMatrix = [];
        }

        // Item 41 — Merchant tier fulfilment rates
        try {
            $merchantTierStats = DB::table('merchants')
                ->join('requests', 'merchants.id', '=', 'requests.merchant_id')
                ->whereNull('merchants.deleted_at')
                ->whereNull('requests.deleted_at')
                ->select(
                    'merchants.tier',
                    DB::raw('COUNT(requests.id) as total_requests'),
                    DB::raw("SUM(CASE WHEN requests.status = 'fulfilled' THEN 1 ELSE 0 END) as fulfilled"),
                    DB::raw("SUM(CASE WHEN requests.status = 'linked' THEN 1 ELSE 0 END) as linked"),
                    DB::raw("SUM(CASE WHEN requests.status = 'rejected' THEN 1 ELSE 0 END) as rejected")
                )
                ->groupBy('merchants.tier')
                ->get()
                ->toArray();

            $merchantTierStats = array_map(function ($row) {
                $row = (array) $row;
                $row['fulfilment_rate'] = $row['total_requests'] > 0
                    ? round(($row['fulfilled'] + $row['linked']) / $row['total_requests'] * 100, 1)
                    : 0;
                return $row;
            }, $merchantTierStats);
        } catch (\Throwable $e) {
            $merchantTierStats = [];
        }

        // Item 43 — CTO-attributed estimate labeling
        try {
            $featuresWithCtoEstimate = DB::table('features')
                ->join('team_members', 'features.cto_estimated_by', '=', 'team_members.id')
                ->whereNull('features.deleted_at')
                ->whereNotNull('features.cto_estimated_hours')
                ->select(
                    'features.id', 'features.title', 'features.estimated_hours',
                    'features.cto_estimated_hours', 'team_members.name as cto_name'
                )
                ->limit(10)
                ->get()
                ->toArray();
        } catch (\Throwable $e) {
            $featuresWithCtoEstimate = [];
        }

        return array_merge($base, [
            'narrative'                  => $narrative,
            'investment_matrix'          => $investmentMatrix,
            'merchant_tier_stats'        => $merchantTierStats,
            'features_with_cto_estimate' => $featuresWithCtoEstimate,
            'data_freshness'             => now()->toIso8601String(),
        ]);
    }

    /**
     * Sales dashboard: only the requests this user submitted.
     */
    public function getSalesData(int $userId): array
    {
        $myRequests = DB::table('requests')
            ->leftJoin('merchants', 'requests.merchant_id', '=', 'merchants.id')
            ->select(
                'requests.id',
                'requests.title',
                'requests.type',
                'requests.urgency',
                'requests.status',
                'requests.demand_count',
                'requests.created_at',
                'merchants.name as merchant_name'
            )
            ->where('requests.requested_by', $userId)
            ->whereNull('requests.deleted_at')
            ->orderByDesc('requests.created_at')
            ->get()
            ->toArray();

        $statsRows = DB::table('requests')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('requested_by', $userId)
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();

        $stats = [];
        foreach ($statsRows as $row) {
            $stats[$row->status] = (int) $row->count;
        }

        // Item 50 — ETA on requests from sprint planning + Item 53 — status labels
        // Batch-fetch sprint_eta if column exists (added by migration 000003)
        $sprintEtaMap = [];
        try {
            $ids = array_column($myRequests, 'id');
            if ($ids) {
                $etaRows = DB::table('requests')
                    ->whereIn('id', $ids)
                    ->select('id', 'sprint_eta', 'linked_sprint_id')
                    ->get();
                foreach ($etaRows as $row) {
                    $sprintEtaMap[$row->id] = $row;
                }
            }
        } catch (\Throwable $e) {
            // sprint_eta / linked_sprint_id columns may not exist yet
        }

        $myRequests = array_map(function ($req) use ($sprintEtaMap) {
            $req = (array) $req;
            $eta = $sprintEtaMap[$req['id']] ?? null;
            $req['sprint_eta']       = $eta?->sprint_eta;
            $req['linked_sprint_id'] = $eta?->linked_sprint_id;

            $req['status_label'] = match ($req['status']) {
                'received'              => 'Received — awaiting review',
                'under_review'          => 'Under Review',
                'clarification_needed'  => 'Clarification Needed',
                'linked'                => 'Linked — planned for development',
                'deferred'              => 'Deferred — postponed',
                'rejected'              => 'Rejected',
                'fulfilled'             => 'Fulfilled',
                default                 => ucfirst(str_replace('_', ' ', $req['status'])),
            };

            return $req;
        }, $myRequests);

        // Item 51 — Merchant lookup with full history
        $merchantId = DB::table('requests')
            ->where('requested_by', $userId)
            ->whereNotNull('merchant_id')
            ->value('merchant_id');

        $merchantHistory = null;
        if ($merchantId) {
            $merchant = DB::table('merchants')->where('id', $merchantId)->first();
            if ($merchant) {
                $merchantHistory = [
                    'merchant'        => (array) $merchant,
                    'total_requests'  => DB::table('requests')
                        ->where('merchant_id', $merchantId)
                        ->whereNull('deleted_at')
                        ->count(),
                    'fulfilled'       => DB::table('requests')
                        ->where('merchant_id', $merchantId)
                        ->where('status', 'fulfilled')
                        ->whereNull('deleted_at')
                        ->count(),
                    'recent_requests' => DB::table('requests')
                        ->where('merchant_id', $merchantId)
                        ->whereNull('deleted_at')
                        ->orderByDesc('created_at')
                        ->select('id', 'title', 'status', 'type', 'created_at')
                        ->limit(5)
                        ->get()
                        ->toArray(),
                ];
            }
        }

        // Item 52 — New product features feed (recently released features)
        $newProductFeatures = DB::table('features')
            ->leftJoin('modules', 'features.module_id', '=', 'modules.id')
            ->whereNull('features.deleted_at')
            ->where('features.status', 'released')
            ->where('features.updated_at', '>=', now()->subDays(30))
            ->select(
                'features.id',
                'features.title',
                'features.description',
                'features.rollout_state',
                'features.updated_at as released_at',
                'modules.name as module_name'
            )
            ->orderByDesc('features.updated_at')
            ->limit(10)
            ->get()
            ->toArray();

        return [
            'myRequests'        => $myRequests,
            'stats'             => $stats,
            'merchant_history'  => $merchantHistory,
            'new_product_features' => $newProductFeatures,
        ];
    }
}
