<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_member_id')->constrained('team_members')->cascadeOnDelete();
            $table->string('activity_type');
            $table->foreignId('feature_id')->nullable()->constrained('features')->nullOnDelete();
            $table->string('duration');
            $table->string('effort_confidence')->nullable();
            $table->string('status');
            $table->text('blocker_reason')->nullable();
            $table->text('note')->nullable();
            $table->boolean('ai_used')->default(false);
            $table->unsignedBigInteger('ai_tool_id')->nullable();
            $table->string('ai_capability')->nullable();
            $table->string('ai_contribution')->nullable();
            $table->string('ai_outcome')->nullable();
            $table->string('ai_time_saved')->nullable();
            $table->text('ai_note')->nullable();
            $table->unsignedBigInteger('cost_rate_id')->nullable();
            $table->date('log_date')->default(DB::raw('(CURRENT_DATE)'));
            $table->boolean('is_same_as_yesterday')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('team_member_id');
            $table->index('feature_id');
            $table->index('log_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
