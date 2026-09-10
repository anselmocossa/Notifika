<?php

namespace Tests\Unit\Channels\Drivers;

use App\Channels\Drivers\MailDriver;
use App\Channels\ResolvedChannel;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class MailDriverTest extends TestCase
{
    private function notification(): Notification
    {
        $notification = new Notification;
        $notification->forceFill([
            'to' => 'destinatario@example.com',
            'subject' => 'Assunto de teste',
            'content' => 'Conteúdo de teste',
            'channel' => 'email',
        ]);

        return $notification;
    }

    private function channel(): ResolvedChannel
    {
        return new ResolvedChannel('mail', [], null, ResolvedChannel::SOURCE_INSTALLATION);
    }

    public function test_returns_sent_when_the_mailer_does_not_throw(): void
    {
        // MAIL_MAILER=array no phpunit.xml: nunca toca na rede (CA-15).
        $result = (new MailDriver)->send($this->notification(), $this->channel());

        $this->assertTrue($result->isSent());
    }

    public function test_a_mailer_exception_is_a_transient_failure(): void
    {
        Mail::shouldReceive('raw')->once()->andThrow(new RuntimeException('SMTP indisponível.'));

        $result = (new MailDriver)->send($this->notification(), $this->channel());

        $this->assertTrue($result->isTransientFailure());
        $this->assertSame('SMTP indisponível.', $result->errorMessage);
    }
}
