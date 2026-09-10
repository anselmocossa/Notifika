<?php

namespace Tests\Unit\Channels\Drivers;

use App\Channels\Drivers\LogDriver;
use App\Channels\ResolvedChannel;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class LogDriverTest extends TestCase
{
    public function test_logs_the_message_and_marks_it_as_sent_without_any_network_call(): void
    {
        Log::spy();

        $notification = new Notification;
        $notification->forceFill([
            'id' => (string) Str::uuid(),
            'to' => '+258841234567',
            'content' => 'Teste',
            'channel' => 'sms',
        ]);

        $result = (new LogDriver)->send($notification, new ResolvedChannel('log', [], null, ResolvedChannel::SOURCE_INSTALLATION));

        $this->assertTrue($result->isSent());
        $this->assertNotNull($result->providerMessageId);

        Log::shouldHaveReceived('info')->once()->withArgs(fn ($message) => $message === 'notifika.log_driver.send');
    }
}
