<?php

namespace App\Providers;

use App\Events\RequestCreated;
use App\Events\TaskStatusChanged;
use App\Events\WorkLogCreated;
use App\Listeners\SendRequestCreatedEmail;
use App\Listeners\SendTaskStatusEmail;
use App\Listeners\SendWorkLogEmail;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

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
        // Register Brevo (Sendinblue) HTTP API mailer transport
        Mail::extend('brevo', function () {
            $apiKey = config('services.brevo.key');
            $factory = new BrevoTransportFactory();
            return $factory->create(new Dsn('brevo+api', 'default', $apiKey));
        });

        // Force HTTPS in production (Render's load balancer terminates SSL)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Register event listeners for email notifications
        Event::listen(TaskStatusChanged::class, SendTaskStatusEmail::class);
        Event::listen(WorkLogCreated::class, SendWorkLogEmail::class);
        Event::listen(RequestCreated::class, SendRequestCreatedEmail::class);

        // Morph map — keeps short type strings in DB consistent with model lookups.
        // Deadline.deadlineable and PmsNotification.notifiable rely on these.
        Relation::morphMap([
            'feature'     => \App\Models\Feature::class,
            'initiative'  => \App\Models\Initiative::class,
            'team_member' => \App\Models\TeamMember::class,
        ]);
    }
}
