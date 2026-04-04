<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('git_provider_id')->constrained('git_providers')->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->string('default_branch')->default('main');
            $table->text('webhook_secret')->nullable();
            $table->string('key_version')->default('v1');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('git_provider_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
