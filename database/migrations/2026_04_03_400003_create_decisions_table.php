<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('context');
            $table->text('options_considered');
            $table->text('chosen_option');
            $table->text('rationale');
            $table->foreignId('decision_maker_id')->constrained('team_members')->restrictOnDelete();
            $table->date('decision_date');
            $table->string('linked_to_type')->nullable(); // 'feature', 'initiative', 'module'
            $table->unsignedBigInteger('linked_to_id')->nullable();
            $table->string('status')->default('proposed');
            $table->foreignId('superseded_by')->nullable()->constrained('decisions')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('decision_maker_id');
            $table->index(['linked_to_type', 'linked_to_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
