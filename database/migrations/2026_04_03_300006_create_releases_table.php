<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->date('release_date');
            $table->string('environment')->default('staging');
            $table->foreignId('deployed_by')->constrained('team_members')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('environment');
            $table->index('release_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
