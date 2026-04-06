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
            "ALTER TABLE merchants ADD COLUMN IF NOT EXISTS tier VARCHAR(255) NOT NULL DEFAULT 'smb'",
            "ALTER TABLE merchants ADD COLUMN IF NOT EXISTS industry VARCHAR(255)",
            "ALTER TABLE merchants ADD COLUMN IF NOT EXISTS account_manager_id VARCHAR(255)",
            "ALTER TABLE merchants ADD COLUMN IF NOT EXISTS contract_value DECIMAL(14,2)",
            "ALTER TABLE merchants ADD COLUMN IF NOT EXISTS contract_start DATE",
            "ALTER TABLE merchants ADD COLUMN IF NOT EXISTS contract_end DATE",
            "ALTER TABLE requests ADD COLUMN IF NOT EXISTS sprint_eta DATE",
            "ALTER TABLE requests ADD COLUMN IF NOT EXISTS linked_sprint_id BIGINT",
        ];

        foreach ($statements as $sql) {
            try { DB::unprepared($sql); } catch (\Throwable $e) {
                Log::warning("Migration DDL skipped: {$e->getMessage()}", ['sql' => $sql]);
            }
        }

        try {
            $fk = DB::selectOne("SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'requests_linked_sprint_id_foreign' AND table_name = 'requests'");
            if (!$fk) {
                DB::unprepared("ALTER TABLE requests ADD CONSTRAINT requests_linked_sprint_id_foreign FOREIGN KEY (linked_sprint_id) REFERENCES sprints(id) ON DELETE SET NULL");
            }
        } catch (\Throwable $e) {
            Log::warning("Migration FK skipped: {$e->getMessage()}");
        }
    }

    public function down(): void
    {
        Schema::table('requests', function ($table) {
            if (Schema::hasColumn('requests', 'linked_sprint_id')) {
                $table->dropForeign(['linked_sprint_id']);
                $table->dropColumn(['sprint_eta', 'linked_sprint_id']);
            }
        });

        Schema::table('merchants', function ($table) {
            $cols = array_filter([
                Schema::hasColumn('merchants', 'tier') ? 'tier' : null,
                Schema::hasColumn('merchants', 'industry') ? 'industry' : null,
                Schema::hasColumn('merchants', 'account_manager_id') ? 'account_manager_id' : null,
                Schema::hasColumn('merchants', 'contract_value') ? 'contract_value' : null,
                Schema::hasColumn('merchants', 'contract_start') ? 'contract_start' : null,
                Schema::hasColumn('merchants', 'contract_end') ? 'contract_end' : null,
            ]);
            if ($cols) $table->dropColumn($cols);
        });
    }
};
