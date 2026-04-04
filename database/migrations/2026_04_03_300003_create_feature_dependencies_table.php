<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_feature_id')->constrained('features')->cascadeOnDelete();
            $table->foreignId('child_feature_id')->constrained('features')->cascadeOnDelete();
            $table->string('type')->default('depends_on');
            $table->timestamps();

            $table->unique(['parent_feature_id', 'child_feature_id', 'type']);
            $table->index('parent_feature_id');
            $table->index('child_feature_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_dependencies');
    }
};
