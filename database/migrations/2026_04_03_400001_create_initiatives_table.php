<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('initiatives', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('origin_type');
            $table->text('business_case');
            $table->string('expected_impact');
            $table->foreignId('owner_id')->constrained('team_members')->restrictOnDelete();
            $table->date('deadline')->nullable();
            $table->string('status')->default('planning');
            $table->unsignedInteger('estimated_features')->nullable();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->unsignedInteger('tenant_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('owner_id');
            $table->index('origin_type');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('initiatives');
    }
};
