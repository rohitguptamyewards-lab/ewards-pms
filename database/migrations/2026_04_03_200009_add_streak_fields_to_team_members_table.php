<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->unsignedInteger('logging_streak_count')->default(0)->after('cost_rate_id');
            $table->date('last_log_date')->nullable()->after('logging_streak_count');
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn(['logging_streak_count', 'last_log_date']);
        });
    }
};
