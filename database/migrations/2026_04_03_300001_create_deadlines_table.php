<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();
            $table->morphs('deadlineable');
            $table->string('type');
            $table->string('state')->default('on_track');
            $table->date('due_date');
            $table->boolean('reminder_sent_7d')->default(false);
            $table->boolean('reminder_sent_3d')->default(false);
            $table->boolean('reminder_sent_1d')->default(false);
            $table->foreignId('cascade_from_id')->nullable()->constrained('deadlines')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('deadlineable_type');
            $table->index('deadlineable_id');
            $table->index('due_date');
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadlines');
    }
};
