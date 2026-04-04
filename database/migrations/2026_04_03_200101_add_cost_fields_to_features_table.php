<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->string('cost_type')->nullable()->after('status');
            $table->decimal('maintenance_cost_monthly', 12, 2)->nullable()->after('cost_type');
            $table->decimal('attributed_revenue', 12, 2)->nullable()->after('maintenance_cost_monthly');
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn(['cost_type', 'maintenance_cost_monthly', 'attributed_revenue']);
        });
    }
};
