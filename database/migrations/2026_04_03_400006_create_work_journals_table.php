<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_member_id')->constrained('team_members')->cascadeOnDelete();
            $table->date('entry_date');
            $table->text('accomplishments');
            $table->text('blockers')->nullable();
            $table->text('plan_for_tomorrow')->nullable();
            $table->text('reflections')->nullable();
            $table->string('mood')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_private')->default(false);
            $table->unsignedInteger('tenant_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_member_id', 'entry_date']);
            $table->index('entry_date');
            $table->index('mood');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_journals');
    }
};
