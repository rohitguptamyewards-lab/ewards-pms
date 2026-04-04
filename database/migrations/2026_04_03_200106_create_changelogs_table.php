<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('changelogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('drafted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('audience_module_ids')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('drafted_by')->references('id')->on('team_members')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('team_members')->nullOnDelete();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelogs');
    }
};
