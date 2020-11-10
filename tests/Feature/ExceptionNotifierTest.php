<?php
namespace Feature;

use Illuminate\Support\Facades\Notification;
use Nqxcode\LaravelExceptionNotifier\Notification\Alert;
use tests\TestCase;
use ExceptionNotifier;

class ExceptionNotifierTest extends TestCase
{
    public function testNotify(): void
    {
        Notification::fake();

        ExceptionNotifier::notify(new \Exception('test'));

        Notification::assertTimesSent( 1, Alert::class);
    }
}
