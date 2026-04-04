<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_tool_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_tool_id');
            $table->unsignedBigInteger('team_member_id');
            $table->timestamp('assigned_at')->nullable()->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('ai_tool_id')->references('id')->on('ai_tools')->cascadeOnDelete();
            $table->foreign('team_member_id')->references('id')->on('team_members')->cascadeOnDelete();

            $table->index(['ai_tool_id', 'team_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_assignments');
    }
};
