<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AiDashboardController;
use App\Http\Controllers\Api\V1\AiKnowledgeBaseController;
use App\Http\Controllers\Api\V1\AiToolController;
use App\Http\Controllers\Api\V1\BugSlaRecordController;
use App\Http\Controllers\Api\V1\BusFactorController;
use App\Http\Controllers\Api\V1\CapacityPlanningController;
use App\Http\Controllers\Api\V1\CeoDashboardController;
use App\Http\Controllers\Api\V1\ChangelogController;
use App\Http\Controllers\Api\V1\CostDashboardController;
use App\Http\Controllers\Api\V1\CostRateController;
use App\Http\Controllers\Api\V1\CostVsImpactController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeadlineController;
use App\Http\Controllers\Api\V1\DecisionController;
use App\Http\Controllers\Api\V1\FeatureController;
use App\Http\Controllers\Api\V1\FeatureSpecVersionController;
use App\Http\Controllers\Api\V1\GitProviderController;
use App\Http\Controllers\Api\V1\GitRepositoryController;
use App\Http\Controllers\Api\V1\IdeaController;
use App\Http\Controllers\Api\V1\InitiativeController;
use App\Http\Controllers\Api\V1\LeaveEntryController;
use App\Http\Controllers\Api\V1\MerchantCommunicationController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\PersonalDashboardController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\PromptTemplateController;
use App\Http\Controllers\Api\V1\ReleaseController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RequestController;
use App\Http\Controllers\Api\V1\SprintController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TeamMemberController;
use App\Http\Controllers\Api\V1\WorkJournalController;
use App\Http\Controllers\Api\V1\WorkLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate']);
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Requests
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [RequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{id}', [RequestController::class, 'show'])->name('requests.show');
    Route::put('/requests/{id}/triage', [RequestController::class, 'triage'])->name('requests.triage');

    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{id}', [ProjectController::class, 'show'])->name('projects.show');

    // Tasks
    Route::get('/tasks', [TaskController::class, 'indexAll'])->name('tasks.index');
    Route::get('/tasks/{id}', [TaskController::class, 'show'])->name('tasks.show');

    // Work Logs
    Route::get('/work-logs', [WorkLogController::class, 'index'])->name('work-logs.index');
    Route::get('/work-logs/create', [WorkLogController::class, 'create'])->name('work-logs.create');
    Route::post('/work-logs', [WorkLogController::class, 'store'])->name('work-logs.store');

    // Features
    Route::get('/features', [FeatureController::class, 'index'])->name('features.index');
    Route::get('/features/kanban', [FeatureController::class, 'kanban'])->name('features.kanban');
    Route::get('/features/create', [FeatureController::class, 'create'])->name('features.create');
    Route::post('/features', [FeatureController::class, 'storeWeb'])->name('features.store');
    Route::get('/features/{id}', [FeatureController::class, 'showPage'])->name('features.show');
    Route::put('/features/{id}', [FeatureController::class, 'updateWeb'])->name('features.update');

    // Initiatives
    Route::get('/initiatives', [InitiativeController::class, 'index'])->name('initiatives.index');
    Route::get('/initiatives/create', [InitiativeController::class, 'create'])->name('initiatives.create');
    Route::post('/initiatives', [InitiativeController::class, 'storeWeb'])->name('initiatives.store');
    Route::get('/initiatives/{id}', [InitiativeController::class, 'showPage'])->name('initiatives.show');
    Route::put('/initiatives/{id}', [InitiativeController::class, 'updateWeb'])->name('initiatives.update');

    // Ideas
    Route::get('/ideas', [IdeaController::class, 'index'])->name('ideas.index');
    Route::get('/ideas/create', [IdeaController::class, 'create'])->name('ideas.create');
    Route::post('/ideas', [IdeaController::class, 'storeWeb'])->name('ideas.store');
    Route::get('/ideas/{id}', [IdeaController::class, 'showPage'])->name('ideas.show');

    // Decisions
    Route::get('/decisions', [DecisionController::class, 'index'])->name('decisions.index');
    Route::get('/decisions/create', [DecisionController::class, 'create'])->name('decisions.create');
    Route::post('/decisions', [DecisionController::class, 'storeWeb'])->name('decisions.store');
    Route::get('/decisions/{id}', [DecisionController::class, 'showPage'])->name('decisions.show');

    // Work Journal
    Route::get('/journal', [WorkJournalController::class, 'index'])->name('journal.index');
    Route::get('/journal/team', [WorkJournalController::class, 'team'])->name('journal.team');
    Route::get('/journal/create', [WorkJournalController::class, 'create'])->name('journal.create');
    Route::post('/journal', [WorkJournalController::class, 'storeWeb'])->name('journal.store');
    Route::get('/journal/{id}', [WorkJournalController::class, 'showPage'])->name('journal.show');

    // Personal Dashboard
    Route::get('/personal/history', [PersonalDashboardController::class, 'history'])->name('personal.history');
    Route::get('/personal/review-prep', [PersonalDashboardController::class, 'reviewPrep'])->name('personal.review-prep');
    Route::get('/personal/profile/{id}', [PersonalDashboardController::class, 'profile'])->name('personal.profile');

    // Onboarding & Offboarding
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding/{id}/start', [OnboardingController::class, 'start'])->name('onboarding.start');
    Route::get('/offboarding', [OnboardingController::class, 'offboardingIndex'])->name('offboarding.index');
    Route::post('/offboarding/{id}/start', [OnboardingController::class, 'startOffboarding'])->name('offboarding.start');

    // Bus Factor
    Route::get('/bus-factor', [BusFactorController::class, 'index'])->name('bus-factor.index');

    // Reports
    Route::get('/reports/work-logs', [ReportController::class, 'workLogs'])->name('reports.work-logs');
    Route::get('/reports/projects', [ReportController::class, 'projects'])->name('reports.projects');
    Route::get('/reports/individual', [ReportController::class, 'individual'])->name('reports.individual');

    // Team Members
    Route::get('/team-members', [TeamMemberController::class, 'index'])->name('team-members.index');
    Route::get('/team-members/{id}', [TeamMemberController::class, 'show'])->name('team-members.show');

    // -------------------------------------------------------------------------
    // Phase 2 — Daily Tracking + Git Integration
    // -------------------------------------------------------------------------

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/create', [ActivityLogController::class, 'create'])->name('activity-logs.create');
    Route::post('/activity-logs', [ActivityLogController::class, 'storeWeb'])->name('activity-logs.store');

    // Sprints
    Route::get('/sprints', [SprintController::class, 'index'])->name('sprints.index');
    Route::get('/sprints/create', [SprintController::class, 'create'])->name('sprints.create');
    Route::post('/sprints', [SprintController::class, 'storeWeb'])->name('sprints.store');
    Route::get('/sprints/{id}', [SprintController::class, 'showPage'])->name('sprints.show');

    // Git Providers
    Route::get('/git-providers', [GitProviderController::class, 'index'])->name('git-providers.index');
    Route::get('/git-providers/create', [GitProviderController::class, 'create'])->name('git-providers.create');
    Route::post('/git-providers', [GitProviderController::class, 'storeWeb'])->name('git-providers.store');

    // Git Repositories
    Route::get('/git-repositories', [GitRepositoryController::class, 'index'])->name('git-repositories.index');
    Route::get('/git-repositories/create', [GitRepositoryController::class, 'create'])->name('git-repositories.create');
    Route::post('/git-repositories', [GitRepositoryController::class, 'storeWeb'])->name('git-repositories.store');

    // -------------------------------------------------------------------------
    // Phase 3 — Deadlines, Bug SLA, Releases, Merchant Communications
    // -------------------------------------------------------------------------

    // Deadlines
    Route::get('/deadlines', [DeadlineController::class, 'index'])->name('deadlines.index');
    Route::get('/deadlines/create', [DeadlineController::class, 'create'])->name('deadlines.create');
    Route::post('/deadlines', [DeadlineController::class, 'storeWeb'])->name('deadlines.store');

    // Bug SLA Records
    Route::get('/bug-sla', [BugSlaRecordController::class, 'index'])->name('bug-sla.index');
    Route::get('/bug-sla/create', [BugSlaRecordController::class, 'create'])->name('bug-sla.create');
    Route::post('/bug-sla', [BugSlaRecordController::class, 'storeWeb'])->name('bug-sla.store');

    // Merchant Communications
    Route::get('/merchant-communications', [MerchantCommunicationController::class, 'index'])->name('merchant-communications.index');
    Route::get('/merchant-communications/create', [MerchantCommunicationController::class, 'create'])->name('merchant-communications.create');
    Route::post('/merchant-communications', [MerchantCommunicationController::class, 'storeWeb'])->name('merchant-communications.store');

    // Releases
    Route::get('/releases', [ReleaseController::class, 'index'])->name('releases.index');
    Route::get('/releases/create', [ReleaseController::class, 'create'])->name('releases.create');
    Route::post('/releases', [ReleaseController::class, 'storeWeb'])->name('releases.store');
    Route::get('/releases/{id}', [ReleaseController::class, 'showPage'])->name('releases.show');

    // -------------------------------------------------------------------------
    // Phase 4 — Cost Intelligence
    // -------------------------------------------------------------------------

    // Cost Rates (CTO only — BR-041)
    Route::get('/cost-rates', [CostRateController::class, 'index'])->name('cost-rates.index');
    Route::get('/cost-rates/create', [CostRateController::class, 'create'])->name('cost-rates.create');
    Route::post('/cost-rates', [CostRateController::class, 'storeWeb'])->name('cost-rates.store');

    // Cost vs Impact
    Route::get('/cost-vs-impact', [CostVsImpactController::class, 'index'])->name('cost-vs-impact.index');

    // CTO Cost Dashboard
    Route::get('/dashboard/cost', [CostDashboardController::class, 'index'])->name('dashboard.cost');

    // CEO Business Dashboard
    Route::get('/dashboard/ceo', [CeoDashboardController::class, 'index'])->name('dashboard.ceo');

    // Changelogs
    Route::get('/changelogs', [ChangelogController::class, 'index'])->name('changelogs.index');
    Route::get('/changelogs/create', [ChangelogController::class, 'create'])->name('changelogs.create');
    Route::post('/changelogs', [ChangelogController::class, 'storeWeb'])->name('changelogs.store');

    // -------------------------------------------------------------------------
    // Phase 5 — AI Intelligence + People & Workload
    // -------------------------------------------------------------------------

    // AI Tools
    Route::get('/ai-tools', [AiToolController::class, 'index'])->name('ai-tools.index');
    Route::get('/ai-tools/create', [AiToolController::class, 'create'])->name('ai-tools.create');
    Route::post('/ai-tools', [AiToolController::class, 'storeWeb'])->name('ai-tools.store');
    Route::get('/ai-tools/{id}', [AiToolController::class, 'showPage'])->name('ai-tools.show');

    // Prompt Templates
    Route::get('/prompt-templates', [PromptTemplateController::class, 'index'])->name('prompt-templates.index');
    Route::get('/prompt-templates/create', [PromptTemplateController::class, 'create'])->name('prompt-templates.create');
    Route::post('/prompt-templates', [PromptTemplateController::class, 'storeWeb'])->name('prompt-templates.store');

    // AI Knowledge Base
    Route::get('/ai-knowledge-base', [AiKnowledgeBaseController::class, 'index'])->name('ai-knowledge-base.index');

    // AI Dashboard (CTO only)
    Route::get('/dashboard/ai', [AiDashboardController::class, 'index'])->name('dashboard.ai');

    // Leave Entries
    Route::get('/leave-entries', [LeaveEntryController::class, 'index'])->name('leave-entries.index');
    Route::get('/leave-entries/create', [LeaveEntryController::class, 'create'])->name('leave-entries.create');
    Route::post('/leave-entries', [LeaveEntryController::class, 'storeWeb'])->name('leave-entries.store');

    // Capacity Planning
    Route::get('/capacity', [CapacityPlanningController::class, 'index'])->name('capacity.index');
});

Route::get('/health', [HealthController::class, 'index']);
