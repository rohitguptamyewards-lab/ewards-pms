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
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role'); // cto, ceo, sales, developer, tester, analyst
            $table->boolean('is_active')->default(true);
            $table->string('department')->nullable();
            $table->date('joining_date')->nullable();
            $table->decimal('weekly_capacity', 5, 2)->default(40);
            $table->decimal('working_hours', 5, 2)->default(8);
            $table->string('timezone')->default('UTC');
            $table->boolean('contractor_flag')->default(false);
            $table->boolean('freelancer_flag')->default(false);
            $table->string('git_username')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->unsignedBigInteger('cost_rate_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('role');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
