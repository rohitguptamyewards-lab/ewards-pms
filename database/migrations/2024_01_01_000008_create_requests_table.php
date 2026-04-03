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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type'); // bug, new_feature, improvement
            $table->string('urgency'); // merchant_blocked, merchant_unhappy, nice_to_have
            $table->string('status')->default('received');
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('team_members')->nullOnDelete();
            $table->foreignId('linked_feature_id')->nullable()->constrained('features')->nullOnDelete();
            $table->unsignedInteger('demand_count')->default(1);
            $table->decimal('revenue_impact', 12, 2)->nullable();
            $table->unsignedInteger('tenant_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('urgency');
            $table->index('status');
            $table->index('merchant_id');
            $table->index('requested_by');
            $table->index('linked_feature_id');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
