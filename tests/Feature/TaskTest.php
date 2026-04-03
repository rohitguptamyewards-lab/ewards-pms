<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $role = 'developer'): \App\Models\TeamMember
    {
        $id = DB::table('team_members')->insertGetId([
            'name'       => 'Task User',
            'email'      => 'task' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'role'       => $role,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return \App\Models\TeamMember::find($id);
    }

    private function createProject(int $ownerId): int
    {
        return DB::table('projects')->insertGetId([
            'name'       => 'Task Project',
            'owner_id'   => $ownerId,
            'status'     => 'active',
            'priority'   => 'high',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // -------------------------------------------------------
    // CREATE TASK
    // -------------------------------------------------------

    public function test_can_create_task_in_project(): void
    {
        $member    = $this->createMember('cto');
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson("/api/v1/projects/{$projectId}/tasks", [
            'title'       => 'Build login screen',
            'description' => 'Implement auth UI',
            'priority'    => 'p1',
            'deadline'    => '2026-05-01',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', [
            'title'      => 'Build login screen',
            'project_id' => $projectId,
            'priority'   => 'p1',
            'status'     => 'open',
        ]);
    }

    public function test_task_requires_title(): void
    {
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson("/api/v1/projects/{$projectId}/tasks", [
            'priority' => 'p2',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('title');
    }

    // -------------------------------------------------------
    // STATUS TRANSITIONS
    // -------------------------------------------------------

    public function test_task_status_transitions_open_to_in_progress(): void
    {
        $member    = $this->createMember('cto');
        $projectId = $this->createProject($member->id);
        $taskId    = DB::table('tasks')->insertGetId([
            'title' => 'Transition Task', 'project_id' => $projectId, 'status' => 'open',
            'priority' => 'p2', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($member)->putJson("/api/v1/tasks/{$taskId}/status", [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $taskId, 'status' => 'in_progress']);
    }

    public function test_task_status_transition_open_to_done_is_invalid(): void
    {
        $member    = $this->createMember('cto');
        $projectId = $this->createProject($member->id);
        $taskId    = DB::table('tasks')->insertGetId([
            'title' => 'Skip Transition', 'project_id' => $projectId, 'status' => 'open',
            'priority' => 'p2', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($member)->putJson("/api/v1/tasks/{$taskId}/status", [
            'status' => 'done',
        ]);

        // Should reject invalid transition (open -> done not allowed)
        $response->assertStatus(422);
    }

    public function test_task_status_in_progress_to_blocked(): void
    {
        $member    = $this->createMember('cto');
        $projectId = $this->createProject($member->id);
        $taskId    = DB::table('tasks')->insertGetId([
            'title' => 'Block Me', 'project_id' => $projectId, 'status' => 'in_progress',
            'priority' => 'p1', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($member)->putJson("/api/v1/tasks/{$taskId}/status", [
            'status' => 'blocked',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $taskId, 'status' => 'blocked']);
    }

    public function test_task_status_in_progress_to_done(): void
    {
        $member    = $this->createMember('cto');
        $projectId = $this->createProject($member->id);
        $taskId    = DB::table('tasks')->insertGetId([
            'title' => 'Finish Me', 'project_id' => $projectId, 'status' => 'in_progress',
            'priority' => 'p2', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($member)->putJson("/api/v1/tasks/{$taskId}/status", [
            'status' => 'done',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $taskId, 'status' => 'done']);
    }

    // -------------------------------------------------------
    // PROGRESS CALCULATION
    // -------------------------------------------------------

    public function test_project_progress_is_100_when_all_tasks_done(): void
    {
        $member    = $this->createMember('cto');
        $projectId = $this->createProject($member->id);

        foreach (['Task 1', 'Task 2', 'Task 3'] as $title) {
            DB::table('tasks')->insert([
                'title' => $title, 'project_id' => $projectId, 'status' => 'done',
                'priority' => 'p3', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($member)->getJson("/api/v1/projects/{$projectId}");

        $response->assertJsonPath('stats.progress', 100);
    }

    public function test_project_progress_is_0_when_no_done_tasks(): void
    {
        $member    = $this->createMember('cto');
        $projectId = $this->createProject($member->id);

        DB::table('tasks')->insert([
            'title' => 'Open Task', 'project_id' => $projectId, 'status' => 'open',
            'priority' => 'p2', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($member)->getJson("/api/v1/projects/{$projectId}");

        $response->assertJsonPath('stats.progress', 0);
    }
}
