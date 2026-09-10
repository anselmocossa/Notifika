<?php

namespace Tests\Unit\Jobs;

use App\Channels\ChannelManager;
use App\Channels\Exceptions\TransientChannelFailureException;
use App\Jobs\SendNotificationJob;
use App\Models\Client;
use App\Models\ClientChannelCredential;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    private function notification(Client $client, string $channel = 'email'): Notification
    {
        return Notification::create([
            'external_id' => Notification::generateExternalId(),
            'client_id' => $client->id,
            'channel' => $channel,
            'to' => $channel === 'email' ? 'destinatario@example.com' : '+258841234567',
            'content' => 'Conteúdo de teste',
            'status' => 'queued',
        ]);
    }

    public function test_a_successful_send_marks_the_notification_as_sent(): void
    {
        $client = Client::factory()->create();
        $notification = $this->notification($client, 'email');

        (new SendNotificationJob($notification))->handle(app(ChannelManager::class));

        $notification->refresh();

        $this->assertSame('sent', $notification->status);
        $this->assertSame('mail', $notification->driver);
        $this->assertSame(1, $notification->attempts);
        $this->assertNotNull($notification->sent_at);
    }

    public function test_a_permanent_failure_marks_failed_without_throwing(): void
    {
        $client = Client::factory()->create();
        ClientChannelCredential::create([
            'client_id' => $client->id,
            'channel' => 'sms',
            'driver' => 'twilio',
            'credentials' => ['account_sid' => 'ACtest', 'auth_token' => 'secret'],
            'sender' => 'NOTIFIKA',
            'is_active' => true,
        ]);

        Http::fake([
            'api.twilio.com/*' => Http::response(['code' => 21211, 'message' => 'invalid to'], 400),
        ]);

        $notification = $this->notification($client, 'sms');

        (new SendNotificationJob($notification))->handle(app(ChannelManager::class));

        $notification->refresh();

        $this->assertSame('failed', $notification->status);
        $this->assertNotNull($notification->failed_at);
        $this->assertNotNull($notification->error_message);
    }

    public function test_a_transient_failure_throws_and_keeps_the_notification_queued_for_retry(): void
    {
        $client = Client::factory()->create();
        ClientChannelCredential::create([
            'client_id' => $client->id,
            'channel' => 'sms',
            'driver' => 'twilio',
            'credentials' => ['account_sid' => 'ACtest', 'auth_token' => 'secret'],
            'sender' => 'NOTIFIKA',
            'is_active' => true,
        ]);

        Http::fake([
            'api.twilio.com/*' => Http::response('Internal Server Error', 500),
        ]);

        $notification = $this->notification($client, 'sms');

        $this->expectException(TransientChannelFailureException::class);

        try {
            (new SendNotificationJob($notification))->handle(app(ChannelManager::class));
        } finally {
            $notification->refresh();

            $this->assertSame('queued', $notification->status);
            $this->assertSame(1, $notification->attempts);
            $this->assertNull($notification->failed_at);
        }
    }

    public function test_an_already_sent_notification_is_not_sent_again(): void
    {
        $client = Client::factory()->create();
        $notification = $this->notification($client, 'email');
        $notification->update(['status' => 'sent']);

        Http::fake();

        (new SendNotificationJob($notification))->handle(app(ChannelManager::class));

        Http::assertNothingSent();
        $this->assertSame(0, $notification->fresh()->attempts);
    }

    public function test_a_channel_not_configured_at_dispatch_time_marks_failed(): void
    {
        $client = Client::factory()->create();
        $notification = $this->notification($client, 'sms');
        // Nenhuma credencial de cliente nem de instalação para 'sms'.

        (new SendNotificationJob($notification))->handle(app(ChannelManager::class));

        $notification->refresh();

        $this->assertSame('failed', $notification->status);
    }

    public function test_failed_marks_the_notification_as_failed(): void
    {
        $client = Client::factory()->create();
        $notification = $this->notification($client, 'email');

        (new SendNotificationJob($notification))->failed(new \RuntimeException('esgotaram-se as tentativas'));

        $notification->refresh();

        $this->assertSame('failed', $notification->status);
        $this->assertSame('esgotaram-se as tentativas', $notification->error_message);
    }
}
