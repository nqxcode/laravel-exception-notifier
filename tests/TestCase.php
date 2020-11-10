<?php
namespace tests;

use Facade\Ignition\IgnitionServiceProvider;
use NotificationChannels\Telegram\TelegramServiceProvider;
use Nqxcode\LaravelExceptionNotifier\Facade;
use Nqxcode\LaravelExceptionNotifier\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->app->make('laravel-exception-notifier.cache')->clear();
        $this->app['config']->set('services.telegram-bot-api.token', env('TELEGRAM_BOT_TOKEN', 'YOUR BOT TOKEN HERE'));
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->register(IgnitionServiceProvider::class);
        $app->register(TelegramServiceProvider::class);
    }

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return ['ExceptionNotifier' => Facade::class];
    }
}
