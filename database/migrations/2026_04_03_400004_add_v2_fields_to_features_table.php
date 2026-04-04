<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            // Drop old string 'initiative' column, replace with FK
            $table->dropColumn('initiative');

            $table->foreignId('initiative_id')
                  ->nullable()
                  ->after('module_id')
                  ->constrained('initiatives')
                  ->nullOnDelete();

            $table->string('origin_type')->nullable()->after('initiative_id');
            $table->string('rollout_state')->nullable()->after('status');

            // Rename qa_owner to qa_owner_id for consistency with model fillable
            $table->renameColumn('qa_owner', 'qa_owner_id');

            $table->index('initiative_id');
            $table->index('origin_type');
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropForeign(['initiative_id']);
            $table->dropIndex(['initiative_id']);
            $table->dropIndex(['origin_type']);
            $table->dropColumn(['initiative_id', 'origin_type', 'rollout_state']);

            $table->string('initiative')->nullable()->after('module_id');
            $table->renameColumn('qa_owner_id', 'qa_owner');
        });
    }
};
