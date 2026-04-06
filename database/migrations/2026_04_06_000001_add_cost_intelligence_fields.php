<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $statements = [
            "ALTER TABLE features ADD COLUMN IF NOT EXISTS is_one_time_cost BOOLEAN NOT NULL DEFAULT FALSE",
            "ALTER TABLE features ADD COLUMN IF NOT EXISTS overhead_multiplier DECIMAL(5,2) NOT NULL DEFAULT 1.0",
            "ALTER TABLE bug_sla_records ADD COLUMN IF NOT EXISTS attributed_dev_cost DECIMAL(12,2)",
            "ALTER TABLE bug_sla_records ADD COLUMN IF NOT EXISTS origin_feature_id BIGINT",
        ];

        foreach ($statements as $sql) {
            try { DB::unprepared($sql); } catch (\Throwable $e) {
                Log::warning("Migration DDL skipped: {$e->getMessage()}", ['sql' => $sql]);
            }
        }

        try {
            $fk = DB::selectOne("SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'bug_sla_records_origin_feature_id_foreign' AND table_name = 'bug_sla_records'");
            if (!$fk) {
                DB::unprepared("ALTER TABLE bug_sla_records ADD CONSTRAINT bug_sla_records_origin_feature_id_foreign FOREIGN KEY (origin_feature_id) REFERENCES features(id) ON DELETE SET NULL");
            }
        } catch (\Throwable $e) {
            Log::warning("Migration FK skipped: {$e->getMessage()}");
        }
    }

    public function down(): void
    {
        Schema::table('bug_sla_records', function ($table) {
            if (Schema::hasColumn('bug_sla_records', 'origin_feature_id')) {
                $table->dropForeign(['origin_feature_id']);
                $table->dropColumn(['attributed_dev_cost', 'origin_feature_id']);
            }
        });

        Schema::table('features', function ($table) {
            $cols = [];
            if (Schema::hasColumn('features', 'is_one_time_cost')) $cols[] = 'is_one_time_cost';
            if (Schema::hasColumn('features', 'overhead_multiplier')) $cols[] = 'overhead_multiplier';
            if ($cols) $table->dropColumn($cols);
        });
    }
};
