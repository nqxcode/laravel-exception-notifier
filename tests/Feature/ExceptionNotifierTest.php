<?php
namespace Feature;

use Illuminate\Support\Facades\Mail;
use Nqxcode\LaravelExceptionNotifier\Mail\Alert;
use tests\TestCase;
use ExceptionNotifier;

class ExceptionNotifierTest extends TestCase
{
    public function testNotify(): void
    {
        Mail::fake();

        ExceptionNotifier::notify(new \Exception('test'));

        Mail::assertSent(Alert::class, 1);
    }
}
