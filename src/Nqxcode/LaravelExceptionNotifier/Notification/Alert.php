<?php

namespace Nqxcode\LaravelExceptionNotifier\Notification;

use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramFile;
use NotificationChannels\Telegram\TelegramMessage;
use Throwable;

class Alert extends Notification
{
    private Throwable $throwable;
    private int $code;
    private string $sapi;
    private string $subject;
    private string $exceptionDumpPath;

    public function __construct(
        Throwable $throwable,
        int $code,
        string $sapi,
        string $subject,
        string $exceptionDumpPath
    ) {
        $this->throwable = $throwable;
        $this->code = $code;
        $this->sapi = $sapi;
        $this->subject = $subject;
        $this->exceptionDumpPath = $exceptionDumpPath;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via()
    {
        return [MailChannel::class, TelegramChannel::class];
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

        $message->attach($this->exceptionDumpPath, ['as' => "exception-dump.tar.gz"]);

        return $message;
    }

    public function toTelegram($notifiable)
    {
        return TelegramFile::create()
            ->content($this->subject)
            ->document($this->exceptionDumpPath, 'exception-dump.tar.gz');
    }
}
