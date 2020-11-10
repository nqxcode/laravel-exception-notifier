<?php

namespace Nqxcode\LaravelExceptionNotifier;

use Illuminate\Notifications\AnonymousNotifiable;
use Nqxcode\LaravelExceptionNotifier\Exception\EmptyAlertMailAddress;
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
    /** @var string|string[] */
    private $alertMailAddress;
    private string $alertMailSubject;
    private string $dumpFilename;

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
     * @param  string|string[]  $address
     */
    public function setAlertMailAddress($address): void
    {
        $this->alertMailAddress = $address;
    }

    /**
     * @param  string  $subject
     */
    public function setAlertMailSubject(string $subject): void
    {
        $this->alertMailSubject = $subject;
    }

    /**
     * @param  string  $name
     */
    public function setDumpFilename(string $name): void
    {
        $this->dumpFilename = $name;
    }

    /**
     * @param  Throwable  $e
     * @param  int  $code
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
        }
    }

    /**
     * @param  Throwable  $e
     * @return string
     */
    private function getExceptionDump(Throwable $e): string
    {
        return str_replace('<script src="//', '<script src="http://', $this->whoops->handleException($e));
    }

    /**
     * Create tar archive with exception dump.
     *
     * @param $content
     * @param $fileName
     * @return string|null path to `tar` file
     */
    private function createExceptionDumpFile(string $content, string $fileName): ?string
    {
        $filePath = null;

        $directory = storage_path('laravel-exception-notifier/attachment');
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0777, true);
        }

        do {
            $tarPath = $directory.'/'.uniqid('exception-dump-', true).'.tar';
        } while (is_file($tarPath));


        $archiveObject = new PharData($tarPath);

        $archiveObject->addFromString("{$fileName}.html", $content);
        $archiveObject->compress(Phar::GZ);

        if (File::isFile($tarPath)) {
            File::delete($tarPath);
        }

        $gzPath = $tarPath.'.gz';
        if (File::isFile($gzPath)) {
            $filePath = $gzPath;
        }

        return $filePath;
    }

    /**
     * Send mail with exception.
     *
     * @param  Throwable  $e
     * @param $code
     * @throws EmptyAlertMailAddress|Throwable
     */
    private function sendNotification(Throwable $e, $code): void
    {
        if (empty($this->alertMailAddress)) {
            throw new EmptyAlertMailAddress('Alert mail address is empty, dump with exception not sent.');
        }

        $exceptionDumpFile = null;
        try {
            $exceptionDump = $this->getExceptionDump($e);
            $exceptionDumpFile = $this->createExceptionDumpFile($exceptionDump, $this->dumpFilename);

            $notification = new AnonymousNotifiable;
            $notification->route('mail', $this->alertMailAddress);
            $notification->notify(
                new Alert(
                    $e,
                    $code,
                    PHP_SAPI,
                    $this->alertMailSubject,
                    $exceptionDump,
                    $exceptionDumpFile,
                    $this->dumpFilename
                )
            );

        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            if (File::isFile($exceptionDumpFile)) {
                File::delete($exceptionDumpFile);
            }
        }
    }
}
