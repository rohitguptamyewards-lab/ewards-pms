<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->string('priority')->nullable(); // p0, p1, p2, p3
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->string('initiative')->nullable();
            $table->text('business_impact')->nullable();
            $table->string('status')->default('backlog');
            $table->date('deadline')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('team_members')->nullOnDelete();
            $table->foreignId('qa_owner')->nullable()->constrained('team_members')->nullOnDelete();
            $table->string('spec_version')->nullable();
            $table->unsignedInteger('tenant_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('module_id');
            $table->index('status');
            $table->index('assigned_to');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
