<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(): \App\Models\TeamMember
    {
        $id = DB::table('team_members')->insertGetId([
            'name'       => 'Commenter',
            'email'      => 'comment' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'role'       => 'developer',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return \App\Models\TeamMember::find($id);
    }

    private function createProject(int $ownerId): int
    {
        return DB::table('projects')->insertGetId([
            'name' => 'Comment Project', 'owner_id' => $ownerId, 'status' => 'active',
            'priority' => 'medium', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function createTask(int $projectId): int
    {
        return DB::table('tasks')->insertGetId([
            'title' => 'Comment Task', 'project_id' => $projectId, 'status' => 'open',
            'priority' => 'p2', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // -------------------------------------------------------
    // CREATE COMMENT
    // -------------------------------------------------------

    public function test_can_add_comment_to_project(): void
    {
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/comments', [
            'commentable_type' => 'project',
            'commentable_id'   => $projectId,
            'body'             => 'Great progress on this project!',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'project',
            'commentable_id'   => $projectId,
            'body'             => 'Great progress on this project!',
            'user_id'          => $member->id,
        ]);
    }

    public function test_can_add_comment_to_task(): void
    {
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);
        $taskId    = $this->createTask($projectId);

        $response = $this->actingAs($member)->postJson('/api/v1/comments', [
            'commentable_type' => 'task',
            'commentable_id'   => $taskId,
            'body'             => 'This task is blocked by API change.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'task',
            'commentable_id'   => $taskId,
        ]);
    }

    public function test_comment_body_is_required(): void
    {
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/comments', [
            'commentable_type' => 'project',
            'commentable_id'   => $projectId,
            // body missing
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('body');
    }

    public function test_comment_with_mention_is_stored_as_plain_text(): void
    {
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/comments', [
            'commentable_type' => 'project',
            'commentable_id'   => $projectId,
            'body'             => '@alice please review this feature.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'body' => '@alice please review this feature.',
        ]);
    }

    // -------------------------------------------------------
    // LIST COMMENTS
    // -------------------------------------------------------

    public function test_can_list_comments_for_project(): void
    {
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);

        DB::table('comments')->insert([
            'commentable_type' => 'project',
            'commentable_id'   => $projectId,
            'user_id'          => $member->id,
            'body'             => 'First comment',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $response = $this->actingAs($member)->getJson("/api/v1/comments?commentable_type=project&commentable_id={$projectId}");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data);
    }

    // -------------------------------------------------------
    // NESTED REPLIES
    // -------------------------------------------------------

    public function test_can_add_reply_to_comment(): void
    {
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);

        $parentId = DB::table('comments')->insertGetId([
            'commentable_type' => 'project',
            'commentable_id'   => $projectId,
            'user_id'          => $member->id,
            'body'             => 'Parent comment',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $response = $this->actingAs($member)->postJson('/api/v1/comments', [
            'commentable_type' => 'project',
            'commentable_id'   => $projectId,
            'body'             => 'Reply to parent',
            'parent_id'        => $parentId,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'parent_id' => $parentId,
            'body'      => 'Reply to parent',
        ]);
    }
}
