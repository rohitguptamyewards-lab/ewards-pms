<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamMemberProfileTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $overrides = []): \App\Models\TeamMember
    {
        $id = DB::table('team_members')->insertGetId(array_merge([
            'name'            => 'Profile User',
            'email'           => 'profile' . uniqid() . '@example.com',
            'password'        => bcrypt('password'),
            'role'            => 'developer',
            'department'      => 'Engineering',
            'joining_date'    => '2025-01-01',
            'weekly_capacity' => 40,
            'is_active'       => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ], $overrides));
        return \App\Models\TeamMember::find($id);
    }

    // -------------------------------------------------------
    // TEAM LIST
    // -------------------------------------------------------

    public function test_team_index_lists_all_members(): void
    {
        $viewer = $this->createMember(['role' => 'cto']);
        $this->createMember(['name' => 'Alice', 'role' => 'developer']);
        $this->createMember(['name' => 'Bob',   'role' => 'tester']);

        $response = $this->actingAs($viewer)->get('/team-members');
        $response->assertStatus(200);
    }

    public function test_team_index_json_returns_members_with_stats(): void
    {
        $viewer = $this->createMember(['role' => 'cto']);

        $response = $this->actingAs($viewer)->getJson('/team-members');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('task_count', (array) $data[0]);
        $this->assertArrayHasKey('project_count', (array) $data[0]);
        $this->assertArrayHasKey('total_hours', (array) $data[0]);
    }

    // -------------------------------------------------------
    // PROFILE SHOW
    // -------------------------------------------------------

    public function test_profile_show_returns_member_data(): void
    {
        $viewer = $this->createMember(['role' => 'cto']);
        $target = $this->createMember(['name' => 'Charlie']);

        $response = $this->actingAs($viewer)->getJson("/team-members/{$target->id}");

        $response->assertStatus(200)
            ->assertJsonPath('member.name', 'Charlie')
            ->assertJsonPath('member.role', 'developer');
    }

    public function test_profile_show_returns_projects_and_tasks(): void
    {
        $viewer = $this->createMember(['role' => 'cto']);
        $target = $this->createMember(['name' => 'Dana']);

        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Profile Project', 'owner_id' => $viewer->id, 'status' => 'active',
            'priority' => 'medium', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('project_members')->insert([
            'project_id' => $projectId, 'user_id' => $target->id, 'added_at' => now(),
        ]);
        DB::table('tasks')->insert([
            'title' => 'Dana Task', 'project_id' => $projectId, 'assigned_to' => $target->id,
            'status' => 'in_progress', 'priority' => 'p2', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($viewer)->getJson("/team-members/{$target->id}");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data['projects']);
        $this->assertNotEmpty($data['tasks']);
        $this->assertEquals(1, $data['stats']['openTasksCount']);
    }

    public function test_profile_show_calculates_total_hours(): void
    {
        $viewer = $this->createMember(['role' => 'cto']);
        $target = $this->createMember();
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Hours Project', 'owner_id' => $viewer->id, 'status' => 'active',
            'priority' => 'low', 'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ([3, 4.5, 2] as $hours) {
            DB::table('work_logs')->insert([
                'user_id'    => $target->id,
                'project_id' => $projectId,
                'log_date'   => now()->toDateString(),
                'hours_spent' => $hours,
                'status'     => 'done',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($viewer)->getJson("/team-members/{$target->id}");

        $response->assertStatus(200);
        $this->assertEquals(9.5, $response->json('stats.totalHours'));
    }

    public function test_profile_show_returns_404_for_missing_member(): void
    {
        $viewer = $this->createMember(['role' => 'cto']);

        $response = $this->actingAs($viewer)->getJson('/team-members/99999');
        $response->assertStatus(404);
    }

    public function test_unauthenticated_cannot_access_team_members(): void
    {
        $response = $this->get('/team-members');
        $response->assertRedirect('/login');
    }
}
