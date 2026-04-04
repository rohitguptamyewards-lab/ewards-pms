<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source')->nullable();
            $table->foreignId('created_by')->constrained('team_members')->restrictOnDelete();
            $table->string('status')->default('new');
            $table->string('promoted_to_type')->nullable(); // 'feature' or 'initiative'
            $table->unsignedBigInteger('promoted_to_id')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('review_reminded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('created_by');
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ideas');
    }
};
