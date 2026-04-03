<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // 'file' (uploaded file) or 'link' (external URL)
            $table->string('type')->default('file')->after('mime_type');
            $table->string('link_url', 2000)->nullable()->after('type');
            $table->text('description')->nullable()->after('link_url');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['type', 'link_url', 'description']);
        });
    }
};
