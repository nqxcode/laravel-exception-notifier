<?php namespace Nqxcode\LaravelExceptionNotifier;

use Throwable;

/**
 * Interface ExceptionNotifierInterface
 * @package Nqxcode\LaravelExceptionNotifier
 */
interface ExceptionNotifierInterface
{
    /**
     * Send notification email with exception dump.
     *
     * @param Throwable $e
     * @param int $code
     */
    public function notify(Throwable $e, int $code = 500): void;
}
