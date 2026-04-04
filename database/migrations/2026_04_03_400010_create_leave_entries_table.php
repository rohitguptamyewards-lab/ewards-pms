<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_member_id')->constrained('team_members')->cascadeOnDelete();
            $table->date('leave_date');
            $table->string('leave_type');
            $table->boolean('half_day')->default(false);
            $table->string('source')->default('manual');
            $table->string('hrms_reference')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['team_member_id', 'leave_date']);
            $table->index('leave_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_entries');
    }
};
