<?php namespace Nqxcode\LaravelExceptionNotifier;

use Nqxcode\LaravelExceptionNotifier\Exception\EmptyAlertMailAddress;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\File;
use Nqxcode\LaravelExceptionNotifier\Mail\Alert;
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
    private Mailer $mailer;
    private ViewFactory $viewFactory;
    private bool $runningInConsole;
    private ExceptionStorage $exceptionStorage;
    /** @var string|string[] */
    private $alertMailAddress;
    private string $alertMailSubject;
    private string $dumpFileName;

    /**
     * ExceptionNotifier constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param Whoops $whoops
     */
    public function setWhoops(Whoops $whoops): void
    {
        $this->whoops = $whoops;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param Mailer $mailer
     */
    public function setMailer(Mailer $mailer): void
    {
        $this->mailer = $mailer;
    }

    /**
     * @param ViewFactory $viewFactory
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
     * @param ExceptionStorage $exceptionStorage
     */
    public function setExceptionStorage(ExceptionStorage $exceptionStorage): void
    {
        $this->exceptionStorage = $exceptionStorage;
    }

    /**
     * @param string|string[] $address
     */
    public function setAlertMailAddress($address): void
    {
        $this->alertMailAddress = $address;
    }

    /**
     * @param string $subject
     */
    public function setAlertMailSubject(string $subject): void
    {
        $this->alertMailSubject = $subject;
    }

    /**
     * @param string $name
     */
    public function setDumpFileName(string $name): void
    {
        $this->dumpFileName = $name;
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
                    if (dirname($e->getFile()) === dirname($consoleAppFile) . '/Input') {
                        return;
                    }
                }
            }

            // Flush all view buffers before rendering template of mail
            $this->viewFactory->flushSections();

            // TODO Disable sending to bcc addresses
            $this->mailer->bcc([]);

            if (!$this->runningInConsole) {
                $this->sendMail($e, $code);

            } elseif ($this->exceptionStorage->available()) {
                if (!$this->exceptionStorage->has($e)) {
                    $this->sendMail($e, $code);
                    $this->exceptionStorage->put($e);
                }
            } else {
                $this->sendMail($e, $code);
            }

        } catch (Throwable $e) {
            $this->logger->alert($e);
        }
    }

    /**
     * Create tar archive with exception dump.
     *
     * @param $content
     * @param $fileName
     * @return string|null path to `tar` file
     */
    private function archive(string $content, string $fileName): ?string
    {
        $filePath = null;

        if (class_exists(PharData::class, true)) {
            try {
                $directory = storage_path('laravel-exception-notifier/attachment');
                if (!File::isDirectory($directory)) {
                    File::makeDirectory($directory, 0777, true);
                }

                do {
                    $tarPath = $directory . '/' . uniqid('exception-dump-', true) . '.tar';
                } while (is_file($tarPath));


                $archiveObject = new PharData($tarPath);

                $archiveObject->addFromString("{$fileName}.html", $content);
                $archiveObject->compress(Phar::GZ);

                if (File::isFile($tarPath)) {
                    File::delete($tarPath);
                }

                $gzPath = $tarPath . '.gz';
                if (File::isFile($gzPath)) {
                    $filePath = $gzPath;
                }

            } catch (Throwable $e) {
                $this->logger->alert($e);
            }
        }

        return $filePath;
    }

    /**
     * Send mail with exception.
     *
     * @param Throwable $e
     * @param $code
     * @throws EmptyAlertMailAddress|Throwable
     */
    private function sendMail(Throwable $e, $code): void
    {
        // Get alert email address

        if (empty($this->alertMailAddress)) {
            throw new EmptyAlertMailAddress('Alert mail address is empty, dump with exception not sent.');
        }

        $archivedDumpFile = null;
        try {
            $this->mailer->send(
                new Alert($e, $code, PHP_SAPI),
                [],
                function (Message $message) use ($e, &$archivedDumpFile) {
                    $message->to($this->alertMailAddress);
                    $message->subject($this->alertMailSubject);

                    $errorDumpContent = str_replace(
                        '<script src="//',
                        '<script src="http://',
                        $this->whoops->handleException($e)
                    );

                    $archivedDumpFile = $this->archive($errorDumpContent, $this->dumpFileName);

                    if (null !== $archivedDumpFile) {
                        $message->attach($archivedDumpFile, ['as' => "{$this->dumpFileName}.tar.gz"]);
                    } else {
                        $message->attachData($errorDumpContent, "{$this->dumpFileName}.html");
                    }
                }
            );

        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            if (File::isFile($archivedDumpFile)) {
                File::delete($archivedDumpFile);
            }
        }
    }
}
