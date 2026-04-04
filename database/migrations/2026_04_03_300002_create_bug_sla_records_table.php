<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_sla_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->string('severity');
            $table->timestamp('sla_deadline')->nullable();
            $table->timestamp('breached_at')->nullable();
            $table->unsignedInteger('reopen_count')->default(0);
            $table->string('root_cause')->nullable();
            $table->string('origin')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('feature_id');
            $table->index('severity');
            $table->index('sla_deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_sla_records');
    }
};
