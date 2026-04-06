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
            $table->string('tier')->default('smb')->after('name')
                ->comment('enterprise | mid_market | smb');
            $table->string('industry')->nullable()->after('tier');
            $table->string('account_manager_id')->nullable()->after('industry');
            $table->decimal('contract_value', 14, 2)->nullable()->after('account_manager_id');
            $table->date('contract_start')->nullable()->after('contract_value');
            $table->date('contract_end')->nullable()->after('contract_start');
        });

        // Sprint ETA field on requests (item 50)
        Schema::table('requests', function (Blueprint $table) {
            $table->date('sprint_eta')->nullable()->after('linked_feature_id')
                ->comment('ETA date derived from sprint planning');
            $table->unsignedBigInteger('linked_sprint_id')->nullable()->after('sprint_eta');
            $table->foreign('linked_sprint_id')->references('id')->on('sprints')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['linked_sprint_id']);
            $table->dropColumn(['sprint_eta', 'linked_sprint_id']);
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['tier', 'industry', 'account_manager_id', 'contract_value', 'contract_start', 'contract_end']);
        });
    }
};
