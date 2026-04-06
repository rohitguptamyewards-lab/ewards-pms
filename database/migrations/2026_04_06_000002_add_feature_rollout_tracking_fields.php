<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item 71 — Feature rollout tracking: rollout_percentage, rollout_notes, rolled_back_at.
 * Item 43 — CTO-attributed estimate labeling.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            // Item 71 — Rollout tracking UI
            $table->unsignedTinyInteger('rollout_percentage')->default(0)->after('rollout_state')
                ->comment('0-100: percentage of users/merchants receiving this feature');
            $table->text('rollout_notes')->nullable()->after('rollout_percentage');
            $table->timestamp('rolled_back_at')->nullable()->after('rollout_notes');
            $table->unsignedBigInteger('rolled_back_by')->nullable()->after('rolled_back_at');
            $table->foreign('rolled_back_by')->references('id')->on('team_members')->nullOnDelete();

            // Item 43 — CTO-attributed estimate
            $table->decimal('cto_estimated_hours', 8, 2)->nullable()->after('estimated_hours')
                ->comment('CTO-provided estimate (labeled separately from dev estimate)');
            $table->unsignedBigInteger('cto_estimated_by')->nullable()->after('cto_estimated_hours');
            $table->foreign('cto_estimated_by')->references('id')->on('team_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropForeign(['rolled_back_by']);
            $table->dropForeign(['cto_estimated_by']);
            $table->dropColumn([
                'rollout_percentage', 'rollout_notes', 'rolled_back_at',
                'rolled_back_by', 'cto_estimated_hours', 'cto_estimated_by',
            ]);
        });
    }
};
