<?php

namespace Iankibet\Messaging;

use Iankibet\Messaging\Contracts\MessageTransport;
use Iankibet\Messaging\Http\Controllers\MessagingController;
use Iankibet\Messaging\Transports\DirectHttpTransport;
use Iankibet\Messaging\Transports\QstashTransport;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/messaging.php', 'messaging');

        $this->app->bind(MessageTransport::class, function ($app) {
            $driver = strtolower((string) config('messaging.driver', 'direct'));

            return match ($driver) {
                'qstash' => $app->make(QstashTransport::class),
                'direct' => $app->make(DirectHttpTransport::class),
                default => throw new \InvalidArgumentException("Unsupported messaging driver [{$driver}]"),
            };
        });

        $this->app->singleton(MessageSender::class);
        $this->app->singleton(MessageDispatcher::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/messaging.php' => $this->app->configPath('messaging.php'),
        ], 'messaging-config');

        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        if (!config('messaging.route.enabled', true)) {
            return;
        }

        Route::middleware(config('messaging.route.middleware', ['api']))
            ->post(
                config('messaging.route.path', 'api/messaging/receive'),
                [MessagingController::class, 'receive'],
            );
    }
}
