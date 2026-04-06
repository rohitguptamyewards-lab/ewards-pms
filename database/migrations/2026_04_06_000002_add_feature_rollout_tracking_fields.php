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
            "ALTER TABLE features ADD COLUMN IF NOT EXISTS rollout_percentage SMALLINT NOT NULL DEFAULT 0",
            "ALTER TABLE features ADD COLUMN IF NOT EXISTS rollout_notes TEXT",
            "ALTER TABLE features ADD COLUMN IF NOT EXISTS rolled_back_at TIMESTAMP",
            "ALTER TABLE features ADD COLUMN IF NOT EXISTS rolled_back_by BIGINT",
            "ALTER TABLE features ADD COLUMN IF NOT EXISTS cto_estimated_hours DECIMAL(8,2)",
            "ALTER TABLE features ADD COLUMN IF NOT EXISTS cto_estimated_by BIGINT",
        ];

        foreach ($statements as $sql) {
            try { DB::unprepared($sql); } catch (\Throwable $e) {
                Log::warning("Migration DDL skipped: {$e->getMessage()}", ['sql' => $sql]);
            }
        }

        $fks = [
            ['features_rolled_back_by_foreign', "ALTER TABLE features ADD CONSTRAINT features_rolled_back_by_foreign FOREIGN KEY (rolled_back_by) REFERENCES team_members(id) ON DELETE SET NULL"],
            ['features_cto_estimated_by_foreign', "ALTER TABLE features ADD CONSTRAINT features_cto_estimated_by_foreign FOREIGN KEY (cto_estimated_by) REFERENCES team_members(id) ON DELETE SET NULL"],
        ];

        foreach ($fks as [$name, $sql]) {
            try {
                $exists = DB::selectOne("SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = ? AND table_name = 'features'", [$name]);
                if (!$exists) { DB::unprepared($sql); }
            } catch (\Throwable $e) {
                Log::warning("Migration FK skipped: {$e->getMessage()}", ['constraint' => $name]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('features', function ($table) {
            if (Schema::hasColumn('features', 'rolled_back_by')) $table->dropForeign(['rolled_back_by']);
            if (Schema::hasColumn('features', 'cto_estimated_by')) $table->dropForeign(['cto_estimated_by']);

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
