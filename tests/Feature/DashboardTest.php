<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $role = 'developer'): \App\Models\TeamMember
    {
        $id = DB::table('team_members')->insertGetId([
            'name'       => 'Dash User',
            'email'      => 'dash' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'role'       => $role,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return \App\Models\TeamMember::find($id);
    }

    // -------------------------------------------------------
    // INDIVIDUAL DASHBOARD
    // -------------------------------------------------------

    public function test_developer_sees_individual_dashboard(): void
    {
        $developer = $this->createMember('developer');

        $response = $this->actingAs($developer)->get('/');
        $response->assertStatus(200);
    }

    public function test_individual_dashboard_json_has_required_keys(): void
    {
        $developer = $this->createMember('developer');

        $response = $this->actingAs($developer)->getJson('/api/v1/dashboard/individual');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'todaysLogs',
                'myTasks',
                'myProjects',
                'weeklyHours',
            ]);
    }

    public function test_individual_dashboard_shows_todays_logs(): void
    {
        $developer = $this->createMember('developer');
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Dash Project', 'owner_id' => $developer->id, 'status' => 'active',
            'priority' => 'medium', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('work_logs')->insert([
            'user_id'    => $developer->id,
            'project_id' => $projectId,
            'log_date'   => now()->toDateString(),
            'hours_spent' => 3,
            'status'     => 'done',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($developer)->getJson('/api/v1/dashboard/individual');

        $data = $response->json();
        $this->assertCount(1, $data['todaysLogs']);
    }

    public function test_individual_dashboard_weekly_hours_sum(): void
    {
        $developer = $this->createMember('developer');
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Weekly Project', 'owner_id' => $developer->id, 'status' => 'active',
            'priority' => 'low', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // 3 logs this week
        foreach ([2, 3, 1.5] as $hours) {
            DB::table('work_logs')->insert([
                'user_id'    => $developer->id,
                'project_id' => $projectId,
                'log_date'   => now()->toDateString(),
                'hours_spent' => $hours,
                'status'     => 'done',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($developer)->getJson('/api/v1/dashboard/individual');

        $this->assertEquals(6.5, $response->json('weeklyHours'));
    }

    // -------------------------------------------------------
    // MANAGER DASHBOARD
    // -------------------------------------------------------

    public function test_cto_sees_manager_dashboard(): void
    {
        $cto = $this->createMember('cto');

        $response = $this->actingAs($cto)->get('/');
        $response->assertStatus(200);
    }

    public function test_manager_dashboard_json_has_required_keys(): void
    {
        $cto = $this->createMember('cto');

        $response = $this->actingAs($cto)->getJson('/api/v1/dashboard/manager');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'activeProjects',
                'openTasks',
                'blockedTasks',
                'overdueTasks',
                'teamWorkload',
                'untriagedRequests',
            ]);
    }

    public function test_manager_dashboard_counts_active_projects(): void
    {
        $cto = $this->createMember('cto');

        DB::table('projects')->insert([
            ['name' => 'Active 1', 'owner_id' => $cto->id, 'status' => 'active', 'priority' => 'high', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Active 2', 'owner_id' => $cto->id, 'status' => 'active', 'priority' => 'low',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Done',     'owner_id' => $cto->id, 'status' => 'completed', 'priority' => 'low', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->actingAs($cto)->getJson('/api/v1/dashboard/manager');

        $this->assertEquals(2, $response->json('activeProjects.count'));
    }

    public function test_manager_dashboard_counts_untriaged_requests(): void
    {
        $cto      = $this->createMember('cto');
        $salesId  = DB::table('team_members')->insertGetId([
            'name' => 'Sales', 'email' => 'sales' . uniqid() . '@x.com', 'password' => bcrypt('p'),
            'role' => 'sales', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $merchantId = DB::table('merchants')->insertGetId([
            'name' => 'Merchant A', 'type' => 'enterprise', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('requests')->insert([
            ['title' => 'R1', 'description' => 'req', 'type' => 'new_feature', 'urgency' => 'nice_to_have', 'status' => 'received', 'merchant_id' => $merchantId, 'requested_by' => $salesId, 'demand_count' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'R2', 'description' => 'req', 'type' => 'bug', 'urgency' => 'merchant_blocked', 'status' => 'received', 'merchant_id' => $merchantId, 'requested_by' => $salesId, 'demand_count' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->actingAs($cto)->getJson('/api/v1/dashboard/manager');

        $this->assertEquals(2, $response->json('untriagedRequests'));
    }
}
