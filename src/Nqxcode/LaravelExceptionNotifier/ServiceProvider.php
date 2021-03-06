<?php namespace Nqxcode\LaravelExceptionNotifier;

use Facade\FlareClient\Flare;
use Facade\Ignition\ErrorPage\IgnitionWhoopsHandler;
use Illuminate;
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
                    $sender->setRoutes(config('laravel-exception-notifier.routes'));
                    $sender->setSubject(config('laravel-exception-notifier.subject'));
                });
            }
        );

        $this->app->bind('laravel-exception-notifier.exception-notifier', ExceptionNotifierInterface::class);
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
