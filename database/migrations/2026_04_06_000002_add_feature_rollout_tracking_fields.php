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
            if (!Schema::hasColumn('features', 'rollout_percentage')) {
                $table->unsignedTinyInteger('rollout_percentage')->default(0)->after('rollout_state')
                    ->comment('0-100: percentage of users/merchants receiving this feature');
            }
            if (!Schema::hasColumn('features', 'rollout_notes')) {
                $table->text('rollout_notes')->nullable()->after('rollout_percentage');
            }
            if (!Schema::hasColumn('features', 'rolled_back_at')) {
                $table->timestamp('rolled_back_at')->nullable()->after('rollout_notes');
            }
            if (!Schema::hasColumn('features', 'rolled_back_by')) {
                $table->unsignedBigInteger('rolled_back_by')->nullable()->after('rolled_back_at');
                $table->foreign('rolled_back_by')->references('id')->on('team_members')->nullOnDelete();
            }
            if (!Schema::hasColumn('features', 'cto_estimated_hours')) {
                $table->decimal('cto_estimated_hours', 8, 2)->nullable()->after('estimated_hours')
                    ->comment('CTO-provided estimate (labeled separately from dev estimate)');
            }
            if (!Schema::hasColumn('features', 'cto_estimated_by')) {
                $table->unsignedBigInteger('cto_estimated_by')->nullable()->after('cto_estimated_hours');
                $table->foreign('cto_estimated_by')->references('id')->on('team_members')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            if (Schema::hasColumn('features', 'rolled_back_by')) {
                $table->dropForeign(['rolled_back_by']);
            }
            if (Schema::hasColumn('features', 'cto_estimated_by')) {
                $table->dropForeign(['cto_estimated_by']);
            }
            $cols = array_filter([
                Schema::hasColumn('features', 'rollout_percentage') ? 'rollout_percentage' : null,
                Schema::hasColumn('features', 'rollout_notes') ? 'rollout_notes' : null,
                Schema::hasColumn('features', 'rolled_back_at') ? 'rolled_back_at' : null,
                Schema::hasColumn('features', 'rolled_back_by') ? 'rolled_back_by' : null,
                Schema::hasColumn('features', 'cto_estimated_hours') ? 'cto_estimated_hours' : null,
                Schema::hasColumn('features', 'cto_estimated_by') ? 'cto_estimated_by' : null,
            ]);
            if ($cols) $table->dropColumn($cols);
        });
    }
};
