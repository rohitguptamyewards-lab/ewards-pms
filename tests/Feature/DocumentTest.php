<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(): \App\Models\TeamMember
    {
        $id = DB::table('team_members')->insertGetId([
            'name'       => 'Doc User',
            'email'      => 'doc' . uniqid() . '@example.com',
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
            'name'       => 'Doc Project',
            'owner_id'   => $ownerId,
            'status'     => 'active',
            'priority'   => 'medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // -------------------------------------------------------
    // FILE UPLOAD
    // -------------------------------------------------------

    public function test_can_upload_file_document(): void
    {
        Storage::fake('local');
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/documents', [
            'type'              => 'file',
            'documentable_type' => 'project',
            'documentable_id'   => $projectId,
            'file'              => UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('documents', [
            'documentable_type' => 'project',
            'documentable_id'   => $projectId,
            'file_name'         => 'spec.pdf',
            'type'              => 'file',
        ]);
    }

    // -------------------------------------------------------
    // LINK DOCUMENT
    // -------------------------------------------------------

    public function test_can_save_external_link(): void
    {
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/documents', [
            'type'              => 'link',
            'documentable_type' => 'project',
            'documentable_id'   => $projectId,
            'link_url'          => 'https://figma.com/design/abc123',
            'file_name'         => 'Figma Design',
            'description'       => 'Main design file',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('documents', [
            'type'      => 'link',
            'link_url'  => 'https://figma.com/design/abc123',
            'file_name' => 'Figma Design',
        ]);
    }

    public function test_link_requires_valid_url(): void
    {
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/documents', [
            'type'              => 'link',
            'documentable_type' => 'project',
            'documentable_id'   => $projectId,
            'link_url'          => 'not-a-url',
            'file_name'         => 'Bad Link',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('link_url');
    }

    public function test_link_requires_file_name(): void
    {
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);

        $response = $this->actingAs($member)->postJson('/api/v1/documents', [
            'type'              => 'link',
            'documentable_type' => 'project',
            'documentable_id'   => $projectId,
            'link_url'          => 'https://example.com',
            // file_name missing
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('file_name');
    }

    // -------------------------------------------------------
    // DELETE
    // -------------------------------------------------------

    public function test_can_delete_file_document(): void
    {
        Storage::fake('local');
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);

        $uploadResponse = $this->actingAs($member)->postJson('/api/v1/documents', [
            'type'              => 'file',
            'documentable_type' => 'project',
            'documentable_id'   => $projectId,
            'file'              => UploadedFile::fake()->create('delete-me.txt', 10),
        ]);

        $docId = $uploadResponse->json('id');

        $deleteResponse = $this->actingAs($member)->deleteJson("/api/v1/documents/{$docId}");
        $deleteResponse->assertStatus(200);

        $this->assertDatabaseMissing('documents', ['id' => $docId]);
    }

    public function test_can_delete_link_document_without_file_error(): void
    {
        $member    = $this->createMember();
        $projectId = $this->createProject($member->id);

        $createResponse = $this->actingAs($member)->postJson('/api/v1/documents', [
            'type'              => 'link',
            'documentable_type' => 'project',
            'documentable_id'   => $projectId,
            'link_url'          => 'https://example.com',
            'file_name'         => 'Test Link',
        ]);

        $docId = $createResponse->json('id');

        $deleteResponse = $this->actingAs($member)->deleteJson("/api/v1/documents/{$docId}");
        $deleteResponse->assertStatus(200);

        $this->assertDatabaseMissing('documents', ['id' => $docId]);
    }

    // -------------------------------------------------------
    // VALIDATION
    // -------------------------------------------------------

    public function test_file_upload_rejects_invalid_documentable_type(): void
    {
        Storage::fake('local');
        $member = $this->createMember();

        $response = $this->actingAs($member)->postJson('/api/v1/documents', [
            'type'              => 'file',
            'documentable_type' => 'invalid_type',
            'documentable_id'   => 1,
            'file'              => UploadedFile::fake()->create('x.txt', 1),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('documentable_type');
    }
}
