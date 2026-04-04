<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Custom PostgreSQL connector for Neon (adds endpoint ID to DSN for SNI-less clients)
        $this->app->bind('db.connector.pgsql', function () {
            return new class extends \Illuminate\Database\Connectors\PostgresConnector {
                protected function getDsn(array $config)
                {
                    $dsn = parent::getDsn($config);
                    if ($endpoint = $config['neon_endpoint'] ?? null) {
                        $dsn .= ";options='endpoint={$endpoint}'";
                    }
                    return $dsn;
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production (Render's load balancer terminates SSL)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Morph map — keeps short type strings in DB consistent with model lookups.
        // Deadline.deadlineable and PmsNotification.notifiable rely on these.
        Relation::morphMap([
            'feature'     => \App\Models\Feature::class,
            'initiative'  => \App\Models\Initiative::class,
            'team_member' => \App\Models\TeamMember::class,
        ]);
    }
}
