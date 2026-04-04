<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_usage_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('feature_id');
            $table->date('date');
            $table->integer('merchants_using_count')->default(0);
            $table->timestamp('first_used_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->integer('total_usage_count')->default(0);
            $table->decimal('revenue_attributed', 14, 2)->default(0);
            $table->integer('abandoned_count')->default(0);
            $table->timestamps();

            $table->foreign('feature_id')->references('id')->on('features');

            $table->unique(['feature_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_usage_snapshots');
    }
};
