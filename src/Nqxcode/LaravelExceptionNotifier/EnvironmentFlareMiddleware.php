<?php namespace Nqxcode\LaravelExceptionNotifier;

use Facade\FlareClient\Report;

/**
 * Class EnvironmentFlareMiddleware
 * @package Nqxcode\LaravelErrorSender
 */
class EnvironmentFlareMiddleware
{
    /**
     * @var bool
     */
    private bool $runningInConsole;

    /**
     * Handle report.
     *
     * @param Report $report
     * @param $next
     * @return mixed
     */
    public function handle(Report $report, $next)
    {
        $context = $report->allContext();

        $context = array_merge($context, $this->getContextGroups());

        $report->userProvidedContext($context);

        return $next($report);
    }

    /**
     * Set running in console.
     *
     * @param $value
     */
    public function setRunningInConsole($value): void
    {
        $this->runningInConsole = $value;
    }

    /**
     * Get context groups.
     *
     * @return array
     */
    private function getContextGroups(): array
    {
        $contextGroups = [];

        $general['PHP sapi'] = PHP_SAPI;

        if (extension_loaded('posix')) {
            $general['Process owner'] = data_get(posix_getpwuid(posix_geteuid()), 'name');
        }

        if ($this->runningInConsole) {
            $general['Command'] = implode(' ', data_get($GLOBALS, 'argv'));
        }

        $contextGroups['context'] = $general;
        $contextGroups['Server Data'] = $_SERVER;
        $contextGroups['Environment Variables'] = $_ENV;

        return $contextGroups;
    }
}
