<?php

namespace Pk305\CbkForex;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Pk305\CbkForex\Commands\FetchCbkForexCommand;
use Pk305\CbkForex\Services\CbkForexService;

class CbkForexServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/cbk-forex.php',
            'cbk-forex'
        );

        $this->app->singleton(CbkForexService::class, fn() => new CbkForexService());
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/Config/cbk-forex.php' => config_path('cbk-forex.php'),
        ], 'cbk-forex-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/Database/migrations/' => database_path('migrations'),
        ], 'cbk-forex-migrations');

        // Load migrations automatically (unless user has published them)
        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');

        // Register Artisan command
        if ($this->app->runningInConsole()) {
            $this->commands([
                FetchCbkForexCommand::class,
            ]);
        }

        // Register daily schedule
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule
                ->command('cbk-forex:fetch')
                ->dailyAt(config('cbk-forex.fetch_time', '14:00'))
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/cbk-forex.log'));
        });
    }
}
