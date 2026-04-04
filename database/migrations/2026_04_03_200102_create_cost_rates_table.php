<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_member_id');
            $table->text('monthly_ctc');
            $table->unsignedInteger('working_hours_per_month')->default(168);
            $table->decimal('overhead_multiplier', 5, 2)->default(1.30);
            $table->text('loaded_hourly_rate');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->unsignedInteger('key_version')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('team_member_id')->references('id')->on('team_members')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('team_members');

            $table->index('team_member_id');
            $table->index('effective_from');
            $table->index('effective_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_rates');
    }
};
