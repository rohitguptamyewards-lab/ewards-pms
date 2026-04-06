<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Items 23, 25 — One-time vs recurring cost flag + overhead multiplier on features.
 * Item 22 — Bug cost attribution field on bug_sla_records.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Item 25 — One-time vs recurring cost flag on features
        Schema::table('features', function (Blueprint $table) {
            $table->boolean('is_one_time_cost')->default(false)->after('cost_type')
                ->comment('true = one-time build cost; false = recurring operational cost');
            $table->decimal('overhead_multiplier', 5, 2)->default(1.0)->after('is_one_time_cost')
                ->comment('Per-feature overhead multiplier applied on top of raw dev cost');
        });

        // Item 22 — Bug cost attribution back to originating feature
        Schema::table('bug_sla_records', function (Blueprint $table) {
            $table->decimal('attributed_dev_cost', 12, 2)->nullable()->after('reopen_count')
                ->comment('Estimated dev cost attributed to fixing this bug');
            $table->unsignedBigInteger('origin_feature_id')->nullable()->after('attributed_dev_cost')
                ->comment('Feature that introduced this bug (for cost attribution)');
            $table->foreign('origin_feature_id')->references('id')->on('features')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bug_sla_records', function (Blueprint $table) {
            $table->dropForeign(['origin_feature_id']);
            $table->dropColumn(['attributed_dev_cost', 'origin_feature_id']);
        });

        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn(['is_one_time_cost', 'overhead_multiplier']);
        });
    }
};
