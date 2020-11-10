<?php

namespace Nqxcode\LaravelExceptionNotifier;

use Illuminate\Notifications\AnonymousNotifiable;
use Nqxcode\LaravelExceptionNotifier\Exception\NoAlertRoutes;
use Illuminate\Support\Facades\File;
use Nqxcode\LaravelExceptionNotifier\Notification\Alert;
use Throwable;
use Illuminate\View\Factory as ViewFactory;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;
use Phar;
use PharData;
use Whoops\Run as Whoops;

/**
 * Class ExceptionNotifier
 * @package Nqxcode\LaravelErrorSender
 */
class ExceptionNotifier implements ExceptionNotifierInterface
{
    private Whoops $whoops;
    private LoggerInterface $logger;
    private ViewFactory $viewFactory;
    private bool $runningInConsole;
    private ExceptionStorage $exceptionStorage;
    private array $routes;
    private string $subject;

    /**
     * ExceptionNotifier constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param  Whoops  $whoops
     */
    public function setWhoops(Whoops $whoops): void
    {
        $this->whoops = $whoops;
    }

    /**
     * @param  LoggerInterface  $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param  ViewFactory  $viewFactory
     */
    public function setViewFactory(ViewFactory $viewFactory): void
    {
        $this->viewFactory = $viewFactory;
    }

    /**
     * @param $value
     */
    public function setRunningInConsole($value): void
    {
        $this->runningInConsole = $value;
    }

    /**
     * @param  ExceptionStorage  $exceptionStorage
     */
    public function setExceptionStorage(ExceptionStorage $exceptionStorage): void
    {
        $this->exceptionStorage = $exceptionStorage;
    }

    /**
     * @param  array  $routes
     */
    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    /**
     * @param  string  $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @param  Throwable  $e
     * @param  int  $code
     * @throws Throwable
     */
    public function notify(Throwable $e, int $code = 500): void
    {
        try {
            if ($this->runningInConsole) {
                $consoleAppFile = (new ReflectionClass(SymfonyConsoleApplication::class))->getFileName();

                // For unknown or ambiguous commands NOT send mail with alert
                if ($e instanceof InvalidArgumentException) {
                    if ($e->getFile() === $consoleAppFile) {
                        return;
                    }
                }

                // For incorrect arguments or options of commands NOT send mail with alert
                if ($e instanceof RuntimeException || $e instanceof LogicException) {
                    if (dirname($e->getFile()) === dirname($consoleAppFile).'/Input') {
                        return;
                    }
                }
            }

            // Flush all view buffers before rendering template of mail
            $this->viewFactory->flushSections();

            if (!$this->runningInConsole) {
                $this->sendNotification($e, $code);
            } elseif ($this->exceptionStorage->available()) {
                if (!$this->exceptionStorage->has($e)) {
                    $this->sendNotification($e, $code);
                    $this->exceptionStorage->put($e);
                }
            } else {
                $this->sendNotification($e, $code);
            }
        } catch (Throwable $e) {
            $this->logger->alert($e);
            throw $e;
        }
    }

    /**
     * Create tar archive with exception dump.
     *
     * @param  Throwable  $e
     * @return string path to `tar` file
     */
    private function createExceptionDumpFile(Throwable $e): string
    {
        $directory = storage_path('laravel-exception-notifier/attachment');
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0777, true);
        }

        do {
            $tarPath = $directory.'/'.uniqid('exception-dump-', true).'.tar';
        } while (File::isFile($tarPath));

        $archiveObject = new PharData($tarPath);

        $content = str_replace('<script src="//', '<script src="http://', $this->whoops->handleException($e));
        $archiveObject->addFromString("exception-dump.html", $content);
        $gzippedObject = $archiveObject->compress(Phar::GZ);

        File::delete($tarPath);

        return $gzippedObject->getPath();
    }

    /**
     * Send mail with exception.
     *
     * @param  Throwable  $e
     * @param $code
     * @throws NoAlertRoutes|Throwable
     */
    private function sendNotification(Throwable $e, $code): void
    {
        if (empty($this->routes)) {
            throw new NoAlertRoutes('No alert routes, dump with exception not sent.');
        }

        $exceptionDumpPath = null;
        try {
            $exceptionDumpPath = $this->createExceptionDumpFile($e);

            $notification = new AnonymousNotifiable();
            foreach ($this->routes as $route) {
                $notification->route($route['channel'], $route['route']);
            }

            if ($notification !== null) {
                $notification->notify(new Alert($e, $code, PHP_SAPI, $this->subject, $exceptionDumpPath));
            }
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            if (File::isFile($exceptionDumpPath)) {
                File::delete($exceptionDumpPath);
            }
        }
    }
}
