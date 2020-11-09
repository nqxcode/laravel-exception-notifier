<?php namespace Nqxcode\LaravelExceptionNotifier;

use \Illuminate\Support\Facades\Facade as BaseFacade;

/**
 * Class Facade
 * @package Nqxcode\LaravelErrorSender
 */
class Facade extends BaseFacade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-exception-notifier.exception-notifier';
    }
}
