<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkLogTest extends TestCase
{
    use RefreshDatabase;

    private function createTeamMember(array $overrides = []): \App\Models\TeamMember
    {
        $id = DB::table('team_members')->insertGetId(array_merge([
            'name'       => 'Worker',
            'email'      => 'worker' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'developer',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return \App\Models\TeamMember::find($id);
    }

    private function createProject(int $ownerId): int
    {
        return DB::table('projects')->insertGetId([
            'name'       => 'Work Project',
            'owner_id'   => $ownerId,
            'status'     => 'active',
            'priority'   => 'medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTask(int $projectId): int
    {
        return DB::table('tasks')->insertGetId([
            'title'      => 'Test Task',
            'project_id' => $projectId,
            'status'     => 'in_progress',
            'priority'   => 'p2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // -------------------------------------------------------
    // CREATE WORK LOG
    // -------------------------------------------------------

    public function test_can_create_work_log_with_status(): void
    {
        $member    = $this->createTeamMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/work-logs', [
            'project_id'  => $projectId,
            'log_date'    => '2026-04-03',
            'hours_spent' => 2.5,
            'status'      => 'done',
            'note'        => 'Completed the module',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('work_logs', [
            'project_id'  => $projectId,
            'hours_spent' => 2.5,
            'status'      => 'done',
        ]);
    }

    public function test_work_log_task_is_optional(): void
    {
        $member    = $this->createTeamMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/work-logs', [
            'project_id'  => $projectId,
            'log_date'    => '2026-04-03',
            'hours_spent' => 1,
            'status'      => 'in_progress',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('work_logs', [
            'project_id' => $projectId,
            'task_id'    => null,
        ]);
    }

    public function test_work_log_can_have_task(): void
    {
        $member    = $this->createTeamMember();
        $projectId = $this->createProject($member->id);
        $taskId    = $this->createTask($projectId);

        $response = $this->actingAs($member)->postJson('/api/v1/work-logs', [
            'project_id'  => $projectId,
            'task_id'     => $taskId,
            'log_date'    => '2026-04-03',
            'hours_spent' => 3,
            'status'      => 'done',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('work_logs', [
            'task_id' => $taskId,
            'status'  => 'done',
        ]);
    }

    public function test_work_log_status_is_required(): void
    {
        $member    = $this->createTeamMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/work-logs', [
            'project_id'  => $projectId,
            'log_date'    => '2026-04-03',
            'hours_spent' => 1,
            // status missing
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('status');
    }

    public function test_work_log_status_must_be_valid(): void
    {
        $member    = $this->createTeamMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/work-logs', [
            'project_id'  => $projectId,
            'log_date'    => '2026-04-03',
            'hours_spent' => 1,
            'status'      => 'invalid_status',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('status');
    }

    public function test_multiple_logs_per_day_are_allowed(): void
    {
        $member    = $this->createTeamMember();
        $projectId = $this->createProject($member->id);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($member)->postJson('/api/v1/work-logs', [
                'project_id'  => $projectId,
                'log_date'    => '2026-04-03',
                'hours_spent' => 2,
                'status'      => 'done',
            ]);
        }

        $count = DB::table('work_logs')
            ->where('user_id', $member->id)
            ->where('log_date', '2026-04-03')
            ->count();

        $this->assertEquals(3, $count);
    }

    public function test_hours_must_be_positive(): void
    {
        $member    = $this->createTeamMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/work-logs', [
            'project_id'  => $projectId,
            'log_date'    => '2026-04-03',
            'hours_spent' => 0,
            'status'      => 'done',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('hours_spent');
    }

    public function test_hours_cannot_exceed_24(): void
    {
        $member    = $this->createTeamMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/work-logs', [
            'project_id'  => $projectId,
            'log_date'    => '2026-04-03',
            'hours_spent' => 25,
            'status'      => 'done',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('hours_spent');
    }

    // -------------------------------------------------------
    // UPDATE / DELETE
    // -------------------------------------------------------

    public function test_can_update_work_log(): void
    {
        $member    = $this->createTeamMember();
        $projectId = $this->createProject($member->id);
        $logId     = DB::table('work_logs')->insertGetId([
            'user_id'    => $member->id,
            'project_id' => $projectId,
            'log_date'   => '2026-04-03',
            'hours_spent' => 2,
            'status'     => 'in_progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($member)->putJson("/api/v1/work-logs/{$logId}", [
            'hours_spent' => 4,
            'status'      => 'done',
            'note'        => 'Updated note',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('work_logs', [
            'id'          => $logId,
            'hours_spent' => 4,
            'status'      => 'done',
        ]);
    }

    public function test_can_soft_delete_work_log(): void
    {
        $member    = $this->createTeamMember();
        $projectId = $this->createProject($member->id);
        $logId     = DB::table('work_logs')->insertGetId([
            'user_id'    => $member->id,
            'project_id' => $projectId,
            'log_date'   => '2026-04-03',
            'hours_spent' => 1,
            'status'     => 'done',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($member)->deleteJson("/api/v1/work-logs/{$logId}");

        $response->assertStatus(200);
        $this->assertNotNull(DB::table('work_logs')->where('id', $logId)->value('deleted_at'));
    }
}
