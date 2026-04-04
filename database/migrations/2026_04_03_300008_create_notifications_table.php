<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pms_notifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable'); // automatically creates index on (notifiable_type, notifiable_id)
            $table->string('type');
            $table->json('data');
            $table->string('channel')->default('in_app');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->timestamps();

            $table->index('read_at');
            $table->index('channel');
            $table->index('scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pms_notifications');
    }
};
