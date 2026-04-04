<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_debt_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->date('week_date');
            $table->integer('debt_backlog_size')->default(0);
            $table->integer('debt_backlog_hours')->default(0);
            $table->integer('debt_velocity')->default(0);
            $table->decimal('debt_to_feature_ratio', 8, 4)->default(0);
            $table->json('debt_age_distribution')->nullable();
            $table->integer('health_score')->default(100);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->foreign('module_id')->references('id')->on('modules')->cascadeOnDelete();

            $table->unique(['module_id', 'week_date']);
            $table->index('week_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_debt_scores');
    }
};
