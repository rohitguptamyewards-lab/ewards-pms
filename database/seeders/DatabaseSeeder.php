<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = now();
        $today = now()->toDateString();
        $password = Hash::make('password');

        // Truncate tables in reverse dependency order
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }
        DB::table('comments')->truncate();
        DB::table('work_logs')->truncate();
        DB::table('requests')->truncate();
        DB::table('features')->truncate();
        DB::table('tasks')->truncate();
        DB::table('project_members')->truncate();
        DB::table('projects')->truncate();
        DB::table('modules')->truncate();
        DB::table('merchants')->truncate();
        DB::table('team_members')->truncate();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // ---------------------------------------------------------------
        // 1. Team Members (6 -- one per role)
        // ---------------------------------------------------------------
        DB::table('team_members')->insert([
            [
                'id'         => 1,
                'name'       => 'Arjun Mehta',
                'email'      => 'arjun@example.com',
                'password'   => $password,
                'role'       => 'cto',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 2,
                'name'       => 'Priya Sharma',
                'email'      => 'priya@example.com',
                'password'   => $password,
                'role'       => 'ceo',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 3,
                'name'       => 'Kavitha Nair',
                'email'      => 'kavitha@example.com',
                'password'   => $password,
                'role'       => 'sales',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 4,
                'name'       => 'Rohit Verma',
                'email'      => 'rohit@example.com',
                'password'   => $password,
                'role'       => 'developer',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 5,
                'name'       => 'Sneha Patel',
                'email'      => 'sneha@example.com',
                'password'   => $password,
                'role'       => 'tester',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 6,
                'name'       => 'Deepak Rao',
                'email'      => 'deepak@example.com',
                'password'   => $password,
                'role'       => 'analyst',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 7,
                'name'       => 'Neha Gupta',
                'email'      => 'neha@example.com',
                'password'   => $password,
                'role'       => 'manager',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 8,
                'name'       => 'Amit Kumar',
                'email'      => 'amit@example.com',
                'password'   => $password,
                'role'       => 'mc_team',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ---------------------------------------------------------------
        // 2. Merchants (3 active)
        // ---------------------------------------------------------------
        DB::table('merchants')->insert([
            [
                'id'         => 1,
                'name'       => 'Bangalore Books Pvt Ltd',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 2,
                'name'       => 'Chennai Grocers',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 3,
                'name'       => 'Mumbai Mart Online',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ---------------------------------------------------------------
        // 3. Modules (2 active)
        // ---------------------------------------------------------------
        DB::table('modules')->insert([
            [
                'id'         => 1,
                'name'       => 'Payment Gateway',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 2,
                'name'       => 'Inventory Management',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ---------------------------------------------------------------
        // 4. Projects (2 active, owned by developer -- id 4)
        // ---------------------------------------------------------------
        DB::table('projects')->insert([
            [
                'id'          => 1,
                'name'        => 'Merchant Dashboard Redesign',
                'description' => 'Complete overhaul of the merchant-facing dashboard with new analytics widgets and improved UX.',
                'status'      => 'active',
                'owner_id'    => 4,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 2,
                'name'        => 'API v2 Migration',
                'description' => 'Migrate all public API endpoints from v1 to v2 with improved rate limiting and pagination.',
                'status'      => 'active',
                'owner_id'    => 4,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);

        // ---------------------------------------------------------------
        // 5. Project Members (developer + tester on both projects)
        // ---------------------------------------------------------------
        DB::table('project_members')->insert([
            ['project_id' => 1, 'user_id' => 4, 'added_at' => $now],
            ['project_id' => 1, 'user_id' => 5, 'added_at' => $now],
            ['project_id' => 2, 'user_id' => 4, 'added_at' => $now],
            ['project_id' => 2, 'user_id' => 5, 'added_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // 6. Tasks (3 per project, various statuses/priorities)
        // ---------------------------------------------------------------
        DB::table('tasks')->insert([
            // -- Project 1: Merchant Dashboard Redesign --
            [
                'id'          => 1,
                'title'       => 'Design new dashboard wireframes',
                'description' => 'Create low-fidelity wireframes for the new merchant dashboard layout.',
                'project_id'  => 1,
                'assigned_to' => 4,
                'status'      => 'done',
                'priority'    => 'p1',
                'deadline'    => now()->subDays(5)->toDateString(),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 2,
                'title'       => 'Implement analytics widgets',
                'description' => 'Build the revenue chart, order funnel, and conversion rate widgets.',
                'project_id'  => 1,
                'assigned_to' => 4,
                'status'      => 'in_progress',
                'priority'    => 'p0',
                'deadline'    => now()->addDays(7)->toDateString(),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 3,
                'title'       => 'Write integration tests for dashboard API',
                'description' => 'Cover all new dashboard endpoints with integration tests.',
                'project_id'  => 1,
                'assigned_to' => 5,
                'status'      => 'open',
                'priority'    => 'p2',
                'deadline'    => now()->addDays(14)->toDateString(),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            // -- Project 2: API v2 Migration --
            [
                'id'          => 4,
                'title'       => 'Define v2 endpoint specifications',
                'description' => 'Document all v2 endpoints with request/response schemas.',
                'project_id'  => 2,
                'assigned_to' => 4,
                'status'      => 'done',
                'priority'    => 'p1',
                'deadline'    => now()->subDays(3)->toDateString(),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 5,
                'title'       => 'Implement rate limiting middleware',
                'description' => 'Add configurable rate limiting for all v2 API routes.',
                'project_id'  => 2,
                'assigned_to' => 4,
                'status'      => 'blocked',
                'priority'    => 'p0',
                'deadline'    => now()->addDays(3)->toDateString(),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 6,
                'title'       => 'Regression test v2 merchant endpoints',
                'description' => 'Full regression suite for merchant CRUD via the v2 API.',
                'project_id'  => 2,
                'assigned_to' => 5,
                'status'      => 'open',
                'priority'    => 'p3',
                'deadline'    => now()->addDays(21)->toDateString(),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);

        // ---------------------------------------------------------------
        // 7. Features (3 -- various statuses, linked to modules)
        // ---------------------------------------------------------------
        DB::table('features')->insert([
            [
                'id'              => 1,
                'title'           => 'Stripe payment integration',
                'description'     => 'Add Stripe as an alternative payment processor for merchants.',
                'type'            => 'feature',
                'priority'        => 'p0',
                'module_id'       => 1,
                'status'          => 'in_progress',
                'assigned_to'     => 4,
                'deadline'        => now()->addDays(30)->toDateString(),
                'estimated_hours' => 40,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'id'              => 2,
                'title'           => 'Bulk inventory import via CSV',
                'description'     => 'Allow merchants to upload a CSV file to bulk-create or update inventory items.',
                'type'            => 'improvement',
                'priority'        => 'p1',
                'module_id'       => 2,
                'status'          => 'backlog',
                'assigned_to'     => null,
                'deadline'        => null,
                'estimated_hours' => 20,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'id'              => 3,
                'title'           => 'Real-time stock level alerts',
                'description'     => 'Push notifications when stock falls below a merchant-configured threshold.',
                'type'            => 'feature',
                'priority'        => 'p2',
                'module_id'       => 2,
                'status'          => 'in_review',
                'assigned_to'     => 5,
                'deadline'        => now()->addDays(14)->toDateString(),
                'estimated_hours' => 16,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
        ]);

        // ---------------------------------------------------------------
        // 8. Requests (4 -- various types, urgencies, statuses)
        // ---------------------------------------------------------------
        DB::table('requests')->insert([
            [
                'id'                 => 1,
                'title'              => 'Add UPI payment option',
                'description'        => 'Merchants in India need UPI as a payment method for their customers.',
                'merchant_id'        => 1,
                'requested_by'       => 3,
                'type'               => 'new_feature',
                'urgency'            => 'merchant_blocked',
                'status'             => 'received',
                'demand_count'       => 5,
                'linked_feature_id'  => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'id'                 => 2,
                'title'              => 'Fix duplicate order emails',
                'description'        => 'Some merchants report receiving two confirmation emails per order.',
                'merchant_id'        => 2,
                'requested_by'       => 3,
                'type'               => 'bug',
                'urgency'            => 'merchant_unhappy',
                'status'             => 'under_review',
                'demand_count'       => 3,
                'linked_feature_id'  => null,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'id'                 => 3,
                'title'              => 'Allow custom invoice templates',
                'description'        => 'Merchants want to upload their own branded invoice template.',
                'merchant_id'        => 3,
                'requested_by'       => 6,
                'type'               => 'improvement',
                'urgency'            => 'nice_to_have',
                'status'             => 'under_review',
                'demand_count'       => 1,
                'linked_feature_id'  => null,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'id'                 => 4,
                'title'              => 'Inventory sync fails for large catalogues',
                'description'        => 'Merchants with over 10,000 SKUs experience timeout errors during sync.',
                'merchant_id'        => 1,
                'requested_by'       => 3,
                'type'               => 'bug',
                'urgency'            => 'merchant_blocked',
                'status'             => 'received',
                'demand_count'       => 2,
                'linked_feature_id'  => 2,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        ]);

        // ---------------------------------------------------------------
        // 9. Work Logs (8 -- various dates, developer + tester)
        // ---------------------------------------------------------------
        DB::table('work_logs')->insert([
            // Developer (id 4) logs
            [
                'id'          => 1,
                'user_id'     => 4,
                'project_id'  => 1,
                'task_id'     => 1,
                'log_date'    => now()->subDays(5)->toDateString(),
                'hours_spent' => 6.0,
                'note'        => 'Completed all wireframe screens in Figma.',
                'blocker'     => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 2,
                'user_id'     => 4,
                'project_id'  => 1,
                'task_id'     => 2,
                'log_date'    => now()->subDays(2)->toDateString(),
                'hours_spent' => 4.5,
                'note'        => 'Built the revenue chart widget with Chart.js.',
                'blocker'     => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 3,
                'user_id'     => 4,
                'project_id'  => 1,
                'task_id'     => 2,
                'log_date'    => $today,
                'hours_spent' => 3.0,
                'note'        => 'Working on the order funnel widget.',
                'blocker'     => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 4,
                'user_id'     => 4,
                'project_id'  => 2,
                'task_id'     => 4,
                'log_date'    => now()->subDays(3)->toDateString(),
                'hours_spent' => 5.0,
                'note'        => 'Finalized v2 endpoint specifications document.',
                'blocker'     => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 5,
                'user_id'     => 4,
                'project_id'  => 2,
                'task_id'     => 5,
                'log_date'    => $today,
                'hours_spent' => 2.0,
                'note'        => 'Investigated rate limiter options.',
                'blocker'     => 'Waiting for DevOps to provision Redis cluster for rate limiting.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            // Tester (id 5) logs
            [
                'id'          => 6,
                'user_id'     => 5,
                'project_id'  => 1,
                'task_id'     => 3,
                'log_date'    => now()->subDays(1)->toDateString(),
                'hours_spent' => 3.5,
                'note'        => 'Set up test framework and wrote first batch of integration tests.',
                'blocker'     => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 7,
                'user_id'     => 5,
                'project_id'  => 1,
                'task_id'     => 3,
                'log_date'    => $today,
                'hours_spent' => 2.5,
                'note'        => 'Continued integration tests for dashboard endpoints.',
                'blocker'     => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 8,
                'user_id'     => 5,
                'project_id'  => 2,
                'task_id'     => 6,
                'log_date'    => now()->subDays(1)->toDateString(),
                'hours_spent' => 2.0,
                'note'        => 'Drafted test plan for v2 merchant endpoints.',
                'blocker'     => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);

        // ---------------------------------------------------------------
        // 10. Comments (3 -- on tasks and requests)
        // ---------------------------------------------------------------
        DB::table('comments')->insert([
            [
                'id'               => 1,
                'commentable_type' => 'task',
                'commentable_id'   => 2,
                'user_id'          => 4,
                'body'             => 'The Chart.js treeshaking issue is resolved. Revenue widget is rendering correctly now.',
                'parent_id'        => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'id'               => 2,
                'commentable_type' => 'task',
                'commentable_id'   => 5,
                'user_id'          => 5,
                'body'             => 'I can help test the rate limiter once the Redis cluster is ready.',
                'parent_id'        => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'id'               => 3,
                'commentable_type' => 'request',
                'commentable_id'   => 1,
                'user_id'          => 3,
                'body'             => 'Multiple merchants have asked about this. Bumping the priority.',
                'parent_id'        => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
        ]);
    }
}
