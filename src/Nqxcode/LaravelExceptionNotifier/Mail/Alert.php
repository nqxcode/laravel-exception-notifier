<?php namespace Nqxcode\LaravelExceptionNotifier\Mail;

use Illuminate\Mail\Mailable;
use Throwable;

class Alert extends Mailable
{
    private Throwable $throwable;
    private int $code;
    private string $sapi;

    public function __construct(Throwable $throwable, int $code, string $sapi)
    {
        $this->throwable = $throwable;
        $this->code = $code;
        $this->sapi = $sapi;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('laravel-exception-notifier::alert')
            ->with('exception', $this->throwable)
            ->with('code', $this->code)
            ->with('sapi', $this->sapi);
    }
}
