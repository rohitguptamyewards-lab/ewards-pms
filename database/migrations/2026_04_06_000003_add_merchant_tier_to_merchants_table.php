<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item 41 — Merchant tier fulfilment rates (enterprise/mid-market/SMB).
 * Item 51 — Merchant lookup with full history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            if (!Schema::hasColumn('merchants', 'tier')) {
                $table->string('tier')->default('smb')->after('name')
                    ->comment('enterprise | mid_market | smb');
            }
            if (!Schema::hasColumn('merchants', 'industry')) {
                $table->string('industry')->nullable()->after('tier');
            }
            if (!Schema::hasColumn('merchants', 'account_manager_id')) {
                $table->string('account_manager_id')->nullable()->after('industry');
            }
            if (!Schema::hasColumn('merchants', 'contract_value')) {
                $table->decimal('contract_value', 14, 2)->nullable()->after('account_manager_id');
            }
            if (!Schema::hasColumn('merchants', 'contract_start')) {
                $table->date('contract_start')->nullable()->after('contract_value');
            }
            if (!Schema::hasColumn('merchants', 'contract_end')) {
                $table->date('contract_end')->nullable()->after('contract_start');
            }
        });

        // Sprint ETA field on requests (item 50)
        Schema::table('requests', function (Blueprint $table) {
            if (!Schema::hasColumn('requests', 'sprint_eta')) {
                $table->date('sprint_eta')->nullable()->after('linked_feature_id')
                    ->comment('ETA date derived from sprint planning');
            }
            if (!Schema::hasColumn('requests', 'linked_sprint_id')) {
                $table->unsignedBigInteger('linked_sprint_id')->nullable()->after('sprint_eta');
                $table->foreign('linked_sprint_id')->references('id')->on('sprints')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'linked_sprint_id')) {
                $table->dropForeign(['linked_sprint_id']);
                $table->dropColumn(['sprint_eta', 'linked_sprint_id']);
            }
        });

        Schema::table('merchants', function (Blueprint $table) {
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
