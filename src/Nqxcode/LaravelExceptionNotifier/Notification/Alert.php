<?php

namespace Nqxcode\LaravelExceptionNotifier\Notification;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class Alert extends Notification
{
    private Throwable $throwable;
    private int $code;
    private string $sapi;
    private string $subject;
    private string $exceptionDump;
    private string $exceptionDumpFile;
    private string $exceptionDumpFilename;

    public function __construct(
        Throwable $throwable,
        int $code,
        string $sapi,
        string $subject,
        string $exceptionDump,
        string $exceptionDumpFile,
        string $exceptionDumpFilename
    ) {
        $this->throwable = $throwable;
        $this->code = $code;
        $this->sapi = $sapi;
        $this->subject = $subject;
        $this->exceptionDump = $exceptionDump;
        $this->exceptionDumpFile = $exceptionDumpFile;
        $this->exceptionDumpFilename = $exceptionDumpFilename;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via()
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = (new MailMessage())
            ->subject($this->subject)
            ->view(
                'laravel-exception-notifier::alert',
                [
                    'exception' => $this->throwable,
                    'code' => $this->code,
                    'sapi' => $this->sapi
                ]
            );

        if (null !== $this->exceptionDumpFile) {
            $message->attach($this->exceptionDumpFile, ['as' => "{$this->exceptionDumpFilename}.tar.gz"]);
        } else {
            $message->attachData($this->exceptionDump, "{$this->exceptionDumpFilename}.html");
        }

        return $message;
    }
}
