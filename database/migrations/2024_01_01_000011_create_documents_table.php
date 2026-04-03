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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('documentable_type');
            $table->unsignedBigInteger('documentable_id');
            $table->foreignId('uploaded_by')->constrained('team_members')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path', 500)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->timestamps();

            $table->index(['documentable_type', 'documentable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
