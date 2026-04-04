<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_log_id')->nullable();
            $table->unsignedBigInteger('ai_tool_id');
            $table->unsignedBigInteger('team_member_id');
            $table->string('capability');
            $table->string('contribution');
            $table->string('outcome');
            $table->string('time_saved');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('prompt_template_id')->nullable();
            $table->timestamps();

            $table->foreign('work_log_id')->references('id')->on('work_logs')->nullOnDelete();
            $table->foreign('ai_tool_id')->references('id')->on('ai_tools')->cascadeOnDelete();
            $table->foreign('team_member_id')->references('id')->on('team_members');
            // FK to prompt_templates added after that table is created (see phase 5 migrations)

            $table->index('ai_tool_id');
            $table->index('team_member_id');
            $table->index('capability');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
