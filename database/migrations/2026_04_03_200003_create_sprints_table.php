<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sprint_number')->unique();
            $table->text('goal')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('total_capacity_hours')->nullable();
            $table->unsignedInteger('committed_hours')->default(0);
            $table->string('status')->default('planning');
            $table->text('capacity_override_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprints');
    }
};
