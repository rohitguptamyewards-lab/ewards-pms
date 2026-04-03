<?php

use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\FeatureController;
use App\Http\Controllers\Api\V1\MerchantController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RequestController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\WorkLogController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth'])->group(function () {
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

    // Comments
    Route::apiResource('comments', CommentController::class)->only(['index', 'store']);

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
});
