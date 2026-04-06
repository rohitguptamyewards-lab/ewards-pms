<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE features ADD COLUMN IF NOT EXISTS rollout_percentage SMALLINT NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE features ADD COLUMN IF NOT EXISTS rollout_notes TEXT");
        DB::statement("ALTER TABLE features ADD COLUMN IF NOT EXISTS rolled_back_at TIMESTAMP");
        DB::statement("ALTER TABLE features ADD COLUMN IF NOT EXISTS rolled_back_by BIGINT");
        DB::statement("ALTER TABLE features ADD COLUMN IF NOT EXISTS cto_estimated_hours DECIMAL(8,2)");
        DB::statement("ALTER TABLE features ADD COLUMN IF NOT EXISTS cto_estimated_by BIGINT");

        // Add FKs only if they don't already exist
        $fk1 = DB::selectOne("SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'features_rolled_back_by_foreign' AND table_name = 'features'");
        if (!$fk1) {
            DB::statement("ALTER TABLE features ADD CONSTRAINT features_rolled_back_by_foreign FOREIGN KEY (rolled_back_by) REFERENCES team_members(id) ON DELETE SET NULL");
        }

        $fk2 = DB::selectOne("SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'features_cto_estimated_by_foreign' AND table_name = 'features'");
        if (!$fk2) {
            DB::statement("ALTER TABLE features ADD CONSTRAINT features_cto_estimated_by_foreign FOREIGN KEY (cto_estimated_by) REFERENCES team_members(id) ON DELETE SET NULL");
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
