<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $role = 'cto'): \App\Models\TeamMember
    {
        $id = DB::table('team_members')->insertGetId([
            'name'       => 'Report User',
            'email'      => 'report' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'role'       => $role,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return \App\Models\TeamMember::find($id);
    }

    // -------------------------------------------------------
    // WORK LOG REPORT
    // -------------------------------------------------------

    public function test_work_log_report_returns_data(): void
    {
        $cto       = $this->createMember('cto');
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Report Project', 'owner_id' => $cto->id, 'status' => 'active',
            'priority' => 'medium', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('work_logs')->insert([
            'user_id'    => $cto->id,
            'project_id' => $projectId,
            'log_date'   => '2026-04-01',
            'hours_spent' => 4,
            'status'     => 'done',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($cto)->getJson('/api/v1/reports/work-logs?date_from=2026-04-01&date_to=2026-04-30');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('logs'));
    }

    public function test_work_log_report_filters_by_user(): void
    {
        $cto = $this->createMember('cto');
        $dev = $this->createMember('developer');
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Filter Project', 'owner_id' => $cto->id, 'status' => 'active',
            'priority' => 'high', 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('work_logs')->insert([
            ['user_id' => $cto->id, 'project_id' => $projectId, 'log_date' => '2026-04-01', 'hours_spent' => 3, 'status' => 'done', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $dev->id, 'project_id' => $projectId, 'log_date' => '2026-04-01', 'hours_spent' => 5, 'status' => 'done', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->actingAs($cto)->getJson("/api/v1/reports/work-logs?user_id={$dev->id}");

        $logs = $response->json('logs');
        foreach ($logs as $log) {
            $this->assertEquals($dev->id, $log['user_id']);
        }
    }

    // -------------------------------------------------------
    // PROJECT REPORT
    // -------------------------------------------------------

    public function test_project_report_returns_list(): void
    {
        $cto = $this->createMember('cto');
        DB::table('projects')->insert([
            ['name' => 'P1', 'owner_id' => $cto->id, 'status' => 'active',    'priority' => 'high', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'P2', 'owner_id' => $cto->id, 'status' => 'completed', 'priority' => 'low',  'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->actingAs($cto)->getJson('/api/v1/reports/projects');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('projects'));
    }

    // -------------------------------------------------------
    // INDIVIDUAL REPORT
    // -------------------------------------------------------

    public function test_individual_report_returns_user_data(): void
    {
        $cto = $this->createMember('cto');

        $response = $this->actingAs($cto)->getJson("/api/v1/reports/individual?user_id={$cto->id}");

        $response->assertStatus(200);
    }

    // -------------------------------------------------------
    // UNAUTHENTICATED
    // -------------------------------------------------------

    public function test_unauthenticated_cannot_access_reports(): void
    {
        $response = $this->get('/reports/work-logs');
        $response->assertRedirect('/login');
    }
}
