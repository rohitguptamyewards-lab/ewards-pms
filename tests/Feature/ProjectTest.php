<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private function createTeamMember(array $overrides = []): int
    {
        return DB::table('team_members')->insertGetId(array_merge([
            'name'       => 'Test User',
            'email'      => 'test' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'developer',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function actingAsMember(int $id): static
    {
        $user = new \App\Models\TeamMember();
        $user->id = $id;
        $user->exists = true;
        return $this->actingAs(
            \App\Models\TeamMember::find($id),
            'web'
        );
    }

    // -------------------------------------------------------
    // CREATE PROJECT
    // -------------------------------------------------------

    public function test_can_create_project_with_all_fields(): void
    {
        $ownerId = $this->createTeamMember(['role' => 'cto']);

        $response = $this->actingAsMember($ownerId)->post('/projects', [
            'name'        => 'My Project',
            'description' => 'A test project',
            'owner_id'    => $ownerId,
            'status'      => 'active',
            'priority'    => 'high',
            'start_date'  => '2026-04-01',
            'end_date'    => '2026-06-30',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'name'     => 'My Project',
            'priority' => 'high',
            'status'   => 'active',
        ]);
    }

    public function test_project_priority_defaults_to_medium(): void
    {
        $ownerId = $this->createTeamMember();

        $this->actingAsMember($ownerId)->post('/projects', [
            'name'     => 'No Priority Project',
            'owner_id' => $ownerId,
        ]);

        $this->assertDatabaseHas('projects', [
            'name'     => 'No Priority Project',
            'priority' => 'medium',
        ]);
    }

    public function test_create_project_requires_name(): void
    {
        $ownerId = $this->createTeamMember();

        $response = $this->actingAsMember($ownerId)->post('/projects', [
            'owner_id' => $ownerId,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_create_project_requires_valid_owner(): void
    {
        $ownerId = $this->createTeamMember();

        $response = $this->actingAsMember($ownerId)->post('/projects', [
            'name'     => 'Bad Owner Project',
            'owner_id' => 99999,
        ]);

        $response->assertSessionHasErrors('owner_id');
    }

    public function test_create_project_rejects_invalid_priority(): void
    {
        $ownerId = $this->createTeamMember();

        $response = $this->actingAsMember($ownerId)->post('/projects', [
            'name'     => 'Bad Priority',
            'owner_id' => $ownerId,
            'priority' => 'ultra',
        ]);

        $response->assertSessionHasErrors('priority');
    }

    public function test_end_date_must_be_after_start_date(): void
    {
        $ownerId = $this->createTeamMember();

        $response = $this->actingAsMember($ownerId)->post('/projects', [
            'name'       => 'Date Test',
            'owner_id'   => $ownerId,
            'start_date' => '2026-06-30',
            'end_date'   => '2026-04-01',
        ]);

        $response->assertSessionHasErrors('end_date');
    }

    // -------------------------------------------------------
    // ADD MEMBER
    // -------------------------------------------------------

    public function test_can_add_member_to_project(): void
    {
        $ownerId  = $this->createTeamMember(['role' => 'cto']);
        $memberId = $this->createTeamMember(['role' => 'developer']);
        $projectId = DB::table('projects')->insertGetId([
            'name'       => 'Test Project',
            'owner_id'   => $ownerId,
            'status'     => 'active',
            'priority'   => 'medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAsMember($ownerId)
            ->postJson("/api/v1/projects/{$projectId}/members", ['user_id' => $memberId]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_members', [
            'project_id' => $projectId,
            'user_id'    => $memberId,
        ]);
    }

    public function test_adding_same_member_twice_does_not_duplicate(): void
    {
        $ownerId  = $this->createTeamMember(['role' => 'cto']);
        $memberId = $this->createTeamMember();
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Dup Member', 'owner_id' => $ownerId, 'status' => 'active',
            'priority' => 'low', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAsMember($ownerId)->postJson("/api/v1/projects/{$projectId}/members", ['user_id' => $memberId]);
        $this->actingAsMember($ownerId)->postJson("/api/v1/projects/{$projectId}/members", ['user_id' => $memberId]);

        $count = DB::table('project_members')
            ->where('project_id', $projectId)
            ->where('user_id', $memberId)
            ->count();

        $this->assertEquals(1, $count);
    }

    // -------------------------------------------------------
    // PROJECT DETAIL PAGE
    // -------------------------------------------------------

    public function test_project_show_returns_tasks_and_stats(): void
    {
        $ownerId   = $this->createTeamMember(['role' => 'cto']);
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Show Test', 'owner_id' => $ownerId, 'status' => 'active',
            'priority' => 'high', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('tasks')->insert([
            'title' => 'Task A', 'project_id' => $projectId, 'status' => 'done',
            'priority' => 'p1', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAsMember($ownerId)->getJson("/api/v1/projects/{$projectId}");

        $response->assertStatus(200)
            ->assertJsonPath('stats.progress', 100)
            ->assertJsonPath('project.name', 'Show Test');
    }

    // -------------------------------------------------------
    // UNAUTHENTICATED
    // -------------------------------------------------------

    public function test_unauthenticated_cannot_access_projects(): void
    {
        $response = $this->get('/projects');
        $response->assertRedirect('/login');
    }
}
