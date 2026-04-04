<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_cost_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('feature_id');
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->json('cost_by_person')->nullable();
            $table->json('cost_by_activity_type')->nullable();
            $table->integer('estimated_hours')->default(0);
            $table->integer('actual_hours')->default(0);
            $table->date('snapshot_date');
            $table->timestamps();

            $table->foreign('feature_id')->references('id')->on('features')->cascadeOnDelete();

            $table->unique(['feature_id', 'snapshot_date']);
            $table->index('snapshot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_cost_snapshots');
    }
};
