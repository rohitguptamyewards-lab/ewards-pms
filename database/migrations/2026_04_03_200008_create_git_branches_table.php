<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('git_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained('repositories')->cascadeOnDelete();
            $table->foreignId('feature_id')->nullable()->constrained('features')->nullOnDelete();
            $table->string('branch_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('repository_id');
            $table->index('feature_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_branches');
    }
};
