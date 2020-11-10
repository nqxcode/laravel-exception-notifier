<?php namespace Nqxcode\LaravelExceptionNotifier;

use Facade\FlareClient\Flare;
use Facade\Ignition\ErrorPage\IgnitionWhoopsHandler;
use Illuminate;
use Illuminate\Cache\FileStore as CacheFileStore;
use Illuminate\Cache\Repository as CacheRepository;
use Whoops\Run as Whoops;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/laravel-exception-notifier.php' => config_path('laravel-exception-notifier.php'),
        ], 'config');

        $this->loadViewsFrom(__DIR__ . '/../../views', 'laravel-exception-notifier');

        $this->app->get(Flare::class)->registerMiddleware(
            tap(new EnvironmentFlareMiddleware, function (EnvironmentFlareMiddleware $middleware) {
                $middleware->setRunningInConsole($this->app->runningInConsole());
            })
        );
    }

    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/laravel-exception-notifier.php', 'laravel-exception-notifier');

        $this->app->singleton(
            ExceptionNotifierInterface::class,
            function ($app) {
                return tap(new ExceptionNotifier, function (ExceptionNotifier $sender) {
                    $sender->setRunningInConsole($this->app->runningInConsole());
                    $sender->setWhoops($this->getWhoops());
                    $sender->setLogger($this->app['log']);
                    $sender->setViewFactory($this->app['view']);
                    $sender->setExceptionStorage(
                        new ExceptionStorage(
                            $this->app->make('laravel-exception-notifier.cache'),
                            config('laravel-exception-notifier.alert_mail.sending_interval')
                        )
                    );

                    $sender->setAlertMailAddress(config('laravel-exception-notifier.alert_mail.address'));
                    $sender->setAlertMailSubject(config('laravel-exception-notifier.alert_mail.subject'));
                    $sender->setDumpFilename(config('laravel-exception-notifier.dump_file_name'));
                });
            }
        );

        $this->app->bind('laravel-exception-notifier.exception-notifier', ExceptionNotifierInterface::class);
        $this->app->singleton('laravel-exception-notifier.cache', function () {
            return new CacheRepository(
                new CacheFileStore(
                    $this->app['files'],
                    storage_path('laravel-exception-notifier/cache')
                )
            );
        });
    }

    /**
     * Get the Whoops.
     *
     * @return Whoops
     */
    protected function getWhoops()
    {
        return tap(new Whoops, function (Whoops $whoops) {
            $whoops->appendHandler($this->ignitionWhoopsHandler());
            $whoops->writeToOutput(false);
            $whoops->allowQuit(false);
        });
    }

    /**
     * Ignition woops handler.
     *
     * @return mixed
     * @throws Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function ignitionWhoopsHandler()
    {
        return $this->app->make(IgnitionWhoopsHandler::class);
    }

    /**
     * @inheritdoc
     */
    public function provides(): array
    {
        return ['laravel-exception-notifier.exception-notifier'];
    }
}
