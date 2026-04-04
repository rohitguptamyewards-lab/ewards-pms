<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprint_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_id')->constrained('sprints')->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->unsignedInteger('committed_hours')->default(0);
            $table->boolean('carried_over')->default(false);
            $table->string('carry_over_reason')->nullable();
            $table->timestamps();

            $table->unique(['sprint_id', 'feature_id']);
            $table->index('feature_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_features');
    }
};
