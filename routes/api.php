<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AiDashboardController;
use App\Http\Controllers\Api\V1\AiKnowledgeBaseController;
use App\Http\Controllers\Api\V1\AiToolController;
use App\Http\Controllers\Api\V1\AiUsageLogController;
use App\Http\Controllers\Api\V1\BlockerController;
use App\Http\Controllers\Api\V1\BugSlaRecordController;
use App\Http\Controllers\Api\V1\CapacityPlanningController;
use App\Http\Controllers\Api\V1\CeoDashboardController;
use App\Http\Controllers\Api\V1\ChangelogController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\CostDashboardController;
use App\Http\Controllers\Api\V1\CostRateController;
use App\Http\Controllers\Api\V1\CostVsImpactController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeadlineController;
use App\Http\Controllers\Api\V1\DecisionController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\FeatureAssignmentController;
use App\Http\Controllers\Api\V1\FeatureController;
use App\Http\Controllers\Api\V1\FeatureSpecVersionController;
use App\Http\Controllers\Api\V1\GitProviderController;
use App\Http\Controllers\Api\V1\GitRepositoryController;
use App\Http\Controllers\Api\V1\GitWebhookController;
use App\Http\Controllers\Api\V1\IdeaController;
use App\Http\Controllers\Api\V1\InitiativeController;
use App\Http\Controllers\Api\V1\LeaveEntryController;
use App\Http\Controllers\Api\V1\MerchantCommunicationController;
use App\Http\Controllers\Api\V1\MerchantController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\PromptTemplateController;
use App\Http\Controllers\Api\V1\ReleaseController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RequestController;
use App\Http\Controllers\Api\V1\SprintController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\WorkJournalController;
use App\Http\Controllers\Api\V1\WorkLogController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->middleware(['auth'])->group(function () {
    // -------------------------------------------------------------------------
    // Phase 1 — Foundation
    // -------------------------------------------------------------------------

    // Requests
    Route::get('requests/check-duplicates', [RequestController::class, 'checkDuplicates']);
    Route::post('requests/{id}/merge', [RequestController::class, 'merge']);
    Route::post('requests/{id}/triage', [RequestController::class, 'triage']);
    Route::apiResource('requests', RequestController::class)->only(['index', 'store', 'show', 'update']);

    // Projects
    Route::post('projects/{id}/members', [ProjectController::class, 'addMember']);
    Route::delete('projects/{id}/members/{userId}', [ProjectController::class, 'removeMember']);
    Route::apiResource('projects', ProjectController::class)->only(['index', 'store', 'show', 'update']);

    // Tasks
    Route::put('tasks/{id}/status', [TaskController::class, 'changeStatus']);
    Route::get('projects/{projectId}/tasks', [TaskController::class, 'index']);
    Route::post('projects/{projectId}/tasks', [TaskController::class, 'store']);
    Route::apiResource('tasks', TaskController::class)->only(['show', 'update']);

    // Work Logs
    Route::apiResource('work-logs', WorkLogController::class)->only(['index', 'store', 'update', 'destroy']);

    // Features
    Route::apiResource('features', FeatureController::class)->only(['index', 'store', 'show', 'update']);
    // Item 71 — Rollout tracking + rollback
    Route::put('features/{id}/rollout', [FeatureController::class, 'updateRollout']);
    Route::post('features/{id}/rollback', [FeatureController::class, 'rollback']);
    // Item 43 — CTO-attributed estimate
    Route::put('features/{id}/cto-estimate', [FeatureController::class, 'setCtoEstimate']);

    // Comments
    Route::get('comments/mentions', [CommentController::class, 'mentionSearch']);
    Route::apiResource('comments', CommentController::class)->only(['index', 'store']);

    // Initiatives
    Route::apiResource('initiatives', InitiativeController::class)->only(['index', 'store', 'show', 'update']);

    // Ideas
    Route::post('ideas/{id}/promote', [IdeaController::class, 'promote']);
    Route::apiResource('ideas', IdeaController::class)->only(['index', 'store', 'show', 'update']);

    // Decisions
    Route::post('decisions/{id}/supersede', [DecisionController::class, 'supersede']);
    Route::put('decisions/{id}/status', [DecisionController::class, 'updateStatus']);
    Route::apiResource('decisions', DecisionController::class)->only(['index', 'store', 'show']);

    // Work Journal
    Route::apiResource('journal', WorkJournalController::class)->only(['index', 'store']);

    // Onboarding / Offboarding
    Route::put('onboarding/{id}/checklist', [OnboardingController::class, 'updateChecklist']);
    Route::put('offboarding/{id}/checklist', [OnboardingController::class, 'updateOffboardingChecklist']);

    // Documents
    Route::post('documents', [DocumentController::class, 'store']);
    Route::get('documents/{id}', [DocumentController::class, 'show']);
    Route::delete('documents/{id}', [DocumentController::class, 'destroy']);

    // Dashboard
    Route::get('dashboard/individual', [DashboardController::class, 'individual']);
    Route::get('dashboard/manager', [DashboardController::class, 'manager']);

    // Reports
    Route::get('reports/work-logs', [ReportController::class, 'workLogs']);
    Route::get('reports/projects', [ReportController::class, 'projects']);
    Route::get('reports/individual', [ReportController::class, 'individual']);

    // Dropdowns
    Route::get('merchants', [MerchantController::class, 'index']);
    Route::get('team-members', fn () => DB::table('team_members')->where('is_active', true)->select('id', 'name', 'role')->get());
    Route::get('modules', fn () => DB::table('modules')->where('is_active', true)->select('id', 'name')->get());

    // -------------------------------------------------------------------------
    // Phase 2 — Daily Tracking + Git Integration
    // -------------------------------------------------------------------------

    // Activity Logs
    Route::apiResource('activity-logs', ActivityLogController::class)->only(['index', 'store', 'update', 'destroy']);

    // Sprints
    Route::post('sprints/{id}/activate', [SprintController::class, 'activate']);
    Route::post('sprints/{id}/complete', [SprintController::class, 'complete']);
    Route::post('sprints/{id}/features', [SprintController::class, 'addFeature']);
    Route::delete('sprints/{id}/features/{featureId}', [SprintController::class, 'removeFeature']);
    Route::apiResource('sprints', SprintController::class)->only(['index', 'store', 'show', 'update']);

    // Feature Spec Versions
    Route::get('features/{featureId}/spec-versions', [FeatureSpecVersionController::class, 'index']);
    Route::post('spec-versions', [FeatureSpecVersionController::class, 'store']);
    Route::get('spec-versions/{id}', [FeatureSpecVersionController::class, 'show']);
    Route::post('spec-versions/{id}/freeze', [FeatureSpecVersionController::class, 'freeze']);
    Route::post('spec-versions/{id}/acknowledge', [FeatureSpecVersionController::class, 'acknowledge']);
    Route::post('spec-versions/{id}/submit-review', [FeatureSpecVersionController::class, 'submitForReview']);

    // Git Providers
    Route::apiResource('git-providers', GitProviderController::class)->only(['index', 'store', 'update', 'destroy']);

    // Git Repositories
    Route::apiResource('git-repositories', GitRepositoryController::class)->only(['index', 'store', 'update', 'destroy']);

    // -------------------------------------------------------------------------
    // Phase 3 — Deadlines, Bug SLA, Blockers, Releases, Merchant Comms
    // -------------------------------------------------------------------------

    // Deadlines
    Route::apiResource('deadlines', DeadlineController::class)->only(['index', 'store', 'update', 'destroy']);

    // Bug SLA Records
    Route::post('bug-sla/{id}/reopen', [BugSlaRecordController::class, 'reopen']);
    Route::apiResource('bug-sla', BugSlaRecordController::class)->only(['index', 'store', 'update']);

    // Blockers
    Route::post('blockers/{id}/resolve', [BlockerController::class, 'resolve']);
    Route::get('features/{featureId}/blockers', [BlockerController::class, 'byFeature']);
    Route::apiResource('blockers', BlockerController::class)->only(['index', 'store']);

    // Merchant Communications
    Route::apiResource('merchant-communications', MerchantCommunicationController::class)->only(['index', 'store', 'update']);

    // Releases
    Route::apiResource('releases', ReleaseController::class)->only(['index', 'store', 'show']);

    // -------------------------------------------------------------------------
    // Phase 4 — Cost Intelligence
    // -------------------------------------------------------------------------

    // Cost Rates (append-only — BR-020, CTO only — BR-041)
    Route::get('cost-rates', [CostRateController::class, 'index']);
    Route::post('cost-rates', [CostRateController::class, 'store']);

    // Cost Dashboard
    Route::get('dashboard/cost', [CostDashboardController::class, 'index']);

    // CEO Dashboard
    Route::get('dashboard/ceo', [CeoDashboardController::class, 'index']);

    // Cost vs Impact
    Route::get('cost-vs-impact', [CostVsImpactController::class, 'index']);

    // Changelogs
    Route::get('changelogs', [ChangelogController::class, 'index']);
    Route::post('changelogs', [ChangelogController::class, 'store']);
    Route::post('changelogs/{id}/approve', [ChangelogController::class, 'approve']);
    Route::post('changelogs/{id}/publish', [ChangelogController::class, 'publish']);
    Route::get('changelogs/published', [ChangelogController::class, 'published']);

    // -------------------------------------------------------------------------
    // Phase 5 — AI Intelligence + People & Workload
    // -------------------------------------------------------------------------

    // AI Tools
    Route::get('ai-tools', [AiToolController::class, 'index']);
    Route::post('ai-tools', [AiToolController::class, 'store']);
    Route::put('ai-tools/{id}', [AiToolController::class, 'update']);
    Route::post('ai-tools/{id}/assign', [AiToolController::class, 'assign']);
    Route::post('ai-tools/{id}/revoke', [AiToolController::class, 'revoke']);

    // AI Usage Logs
    Route::get('ai-usage-logs', [AiUsageLogController::class, 'index']);
    Route::post('ai-usage-logs', [AiUsageLogController::class, 'store']);
    Route::get('ai-usage-logs/effectiveness', [AiUsageLogController::class, 'effectivenessMatrix']);

    // AI Knowledge Base
    Route::get('ai-knowledge-base', [AiKnowledgeBaseController::class, 'index']);

    // AI Dashboard
    Route::get('dashboard/ai', [AiDashboardController::class, 'index']);

    // Prompt Templates
    Route::get('prompt-templates', [PromptTemplateController::class, 'index']);
    Route::post('prompt-templates', [PromptTemplateController::class, 'store']);
    Route::put('prompt-templates/{id}', [PromptTemplateController::class, 'update']);
    Route::get('prompt-templates/most-used', [PromptTemplateController::class, 'mostUsed']);

    // Feature Assignments
    Route::get('feature-assignments', [FeatureAssignmentController::class, 'index']);
    Route::post('feature-assignments', [FeatureAssignmentController::class, 'store']);
    Route::get('features/{featureId}/assignments', [FeatureAssignmentController::class, 'byFeature']);
    Route::post('feature-assignments/{id}/complete', [FeatureAssignmentController::class, 'complete']);
    Route::post('feature-assignments/{id}/remove', [FeatureAssignmentController::class, 'remove']);
    Route::get('team-members/{memberId}/estimation-accuracy', [FeatureAssignmentController::class, 'estimationAccuracy']);

    // Leave Entries
    Route::apiResource('leave-entries', LeaveEntryController::class)->only(['index', 'store', 'update', 'destroy']);

    // Capacity Planning
    Route::get('capacity', [CapacityPlanningController::class, 'index']);
    Route::get('capacity/forecast/{initiativeId}', [CapacityPlanningController::class, 'forecast']);
});

// Git Webhook — no auth, uses HMAC signature verification
// Item 68 — Rate limiting on webhook endpoints (60 per minute per repo)
Route::prefix('v1')->middleware(['throttle:60,1'])->group(function () {
    Route::post('webhooks/git/{repoName}', [GitWebhookController::class, 'handle']);
});
