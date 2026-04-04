<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->foreignId('team_member_id')->constrained('team_members')->cascadeOnDelete();
            $table->string('role');
            $table->string('state')->default('assigned');
            $table->unsignedInteger('estimated_hours')->default(0);
            $table->unsignedInteger('actual_hours')->default(0);
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['feature_id', 'team_member_id']);
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_assignments');
    }
};
