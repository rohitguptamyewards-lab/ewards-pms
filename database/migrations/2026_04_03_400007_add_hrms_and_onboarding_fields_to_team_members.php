<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $existing = Schema::getColumnListing('team_members');

            // HRMS readiness fields
            if (!in_array('employee_id', $existing)) $table->string('employee_id')->nullable()->after('email');
            if (!in_array('department', $existing)) $table->string('department')->nullable()->after('role');
            if (!in_array('join_date', $existing)) $table->date('join_date')->nullable()->after('department');
            if (!in_array('designation', $existing)) $table->string('designation')->nullable()->after('join_date');
            if (!in_array('reporting_to', $existing)) $table->string('reporting_to')->nullable()->after('designation');

            // Onboarding fields
            if (!in_array('onboarding_status', $existing)) $table->string('onboarding_status')->nullable()->after('reporting_to');
            if (!in_array('buddy_id', $existing)) $table->foreignId('buddy_id')->nullable()->after('onboarding_status');
            if (!in_array('onboarding_due_date', $existing)) $table->date('onboarding_due_date')->nullable()->after('buddy_id');
            if (!in_array('onboarding_checklist', $existing)) $table->json('onboarding_checklist')->nullable()->after('onboarding_due_date');

            // Offboarding fields
            if (!in_array('exit_date', $existing)) $table->date('exit_date')->nullable()->after('onboarding_checklist');
            if (!in_array('offboarding_status', $existing)) $table->string('offboarding_status')->nullable()->after('exit_date');
            if (!in_array('offboarding_checklist', $existing)) $table->json('offboarding_checklist')->nullable()->after('offboarding_status');
            if (!in_array('exit_notes', $existing)) $table->text('exit_notes')->nullable()->after('offboarding_checklist');

            // Bus factor / knowledge coverage
            if (!in_array('skill_tags', $existing)) $table->json('skill_tags')->nullable()->after('exit_notes');
            if (!in_array('module_expertise', $existing)) $table->json('module_expertise')->nullable()->after('skill_tags');
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id', 'department', 'join_date', 'designation', 'reporting_to',
                'onboarding_status', 'buddy_id', 'onboarding_due_date', 'onboarding_checklist',
                'exit_date', 'offboarding_status', 'offboarding_checklist', 'exit_notes',
                'skill_tags', 'module_expertise',
            ]);
        });
    }
};
