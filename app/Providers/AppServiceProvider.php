<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Morph map — keeps short type strings in DB consistent with model lookups.
        // Deadline.deadlineable and PmsNotification.notifiable rely on these.
        Relation::morphMap([
            'feature'     => \App\Models\Feature::class,
            'initiative'  => \App\Models\Initiative::class,
            'team_member' => \App\Models\TeamMember::class,
        ]);
    }
}
