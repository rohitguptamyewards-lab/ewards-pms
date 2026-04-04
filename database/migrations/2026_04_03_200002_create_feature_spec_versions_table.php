<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_spec_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->unsignedInteger('version_number')->default(1);
            $table->longText('content');
            $table->foreignId('author_id')->constrained('team_members')->cascadeOnDelete();
            $table->text('change_summary')->nullable();
            $table->string('state')->default('draft');
            $table->foreignId('acknowledged_by')->nullable()->constrained('team_members')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('change_type')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['feature_id', 'version_number']);
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_spec_versions');
    }
};
