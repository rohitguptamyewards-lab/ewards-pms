<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('git_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider_type');
            $table->string('base_url')->nullable();
            $table->text('credentials')->nullable();
            $table->string('key_version')->default('v1');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('provider_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_providers');
    }
};
