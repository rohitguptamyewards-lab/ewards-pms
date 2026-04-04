<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_tool_id')->constrained('ai_tools')->cascadeOnDelete();
            $table->string('capability');
            $table->string('title');
            $table->text('content');
            $table->json('tags')->nullable();
            $table->foreignId('created_by')->constrained('team_members')->cascadeOnDelete();
            $table->unsignedInteger('usage_count')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('capability');
            $table->index('ai_tool_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_templates');
    }
};
