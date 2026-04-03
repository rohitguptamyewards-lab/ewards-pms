<?php

use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\FeatureController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RequestController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TeamMemberController;
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

    // Reports
    Route::get('/reports/work-logs', [ReportController::class, 'workLogs'])->name('reports.work-logs');
    Route::get('/reports/projects', [ReportController::class, 'projects'])->name('reports.projects');
    Route::get('/reports/individual', [ReportController::class, 'individual'])->name('reports.individual');

    // Team Members
    Route::get('/team-members', [TeamMemberController::class, 'index'])->name('team-members.index');
    Route::get('/team-members/{id}', [TeamMemberController::class, 'show'])->name('team-members.show');
});

Route::get('/health', [HealthController::class, 'index']);
