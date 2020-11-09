<?php
namespace tests;

use Facade\Ignition\IgnitionServiceProvider;
use Nqxcode\LaravelExceptionNotifier\Facade;
use Nqxcode\LaravelExceptionNotifier\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->app->make('laravel-exception-notifier.cache')->clear();
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
