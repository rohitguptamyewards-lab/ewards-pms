<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->string('status')->default('done')->after('blocker'); // done, in_progress, blocked
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
