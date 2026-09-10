<?php

namespace Tests\Unit\Channels\Drivers;

use App\Channels\Drivers\TwilioSmsDriver;
use App\Channels\ResolvedChannel;
use App\Models\Notification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CA-15: nenhum destes testes faz uma chamada HTTP real — Http::fake()
 * intercepta tudo.
 */
class TwilioSmsDriverTest extends TestCase
{
    private function notification(string $to = '+258841234567', string $content = 'Marca: Notifika. Teste.'): Notification
    {
        $notification = new Notification;
        $notification->forceFill([
            'id' => (string) Str::uuid(),
            'to' => $to,
            'content' => $content,
            'channel' => 'sms',
        ]);

        return $notification;
    }

    private function channel(string $sender = 'NOTIFIKA'): ResolvedChannel
    {
        return new ResolvedChannel(
            driver: 'twilio',
            credentials: ['account_sid' => 'ACtest', 'auth_token' => 'secret-token'],
            sender: $sender,
            source: ResolvedChannel::SOURCE_INSTALLATION,
        );
    }

    public function test_a_successful_send_returns_sent_with_provider_id_and_cost(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response([
                'sid' => 'SMxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                'status' => 'queued',
                'price' => '-0.41700',
                'price_unit' => 'USD',
            ], 201),
        ]);

        $result = (new TwilioSmsDriver)->send($this->notification(), $this->channel());

        $this->assertTrue($result->isSent());
        $this->assertSame('SMxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', $result->providerMessageId);
        $this->assertSame('0.41700', $result->cost);
        $this->assertSame('USD', $result->costCurrency);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/ACtest/Messages.json'
                && $request['To'] === '+258841234567'
                && $request['From'] === 'NOTIFIKA';
        });
    }

    public function test_invalid_to_number_21211_is_permanent(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response([
                'code' => 21211,
                'message' => "The 'To' number is not a valid phone number.",
                'status' => 400,
            ], 400),
        ]);

        $result = (new TwilioSmsDriver)->send($this->notification(), $this->channel());

        $this->assertTrue($result->isPermanentFailure());
    }

    public function test_unsubscribed_recipient_21610_is_permanent(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['code' => 21610, 'message' => 'stop'], 400),
        ]);

        $result = (new TwilioSmsDriver)->send($this->notification(), $this->channel());

        $this->assertTrue($result->isPermanentFailure());
    }

    public function test_region_not_permitted_21408_is_permanent(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['code' => 21408, 'message' => 'geo blocked'], 400),
        ]);

        $result = (new TwilioSmsDriver)->send($this->notification(), $this->channel());

        $this->assertTrue($result->isPermanentFailure());
    }

    public function test_daily_account_limit_63038_is_permanent(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['code' => 63038, 'message' => 'daily limit'], 400),
        ]);

        $result = (new TwilioSmsDriver)->send($this->notification(), $this->channel());

        $this->assertTrue($result->isPermanentFailure());
    }

    public function test_rate_limit_20429_is_transient(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['code' => 20429, 'message' => 'too many requests'], 429),
        ]);

        $result = (new TwilioSmsDriver)->send($this->notification(), $this->channel());

        $this->assertTrue($result->isTransientFailure());
    }

    public function test_http_429_without_a_twilio_code_is_transient(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response('Too Many Requests', 429),
        ]);

        $result = (new TwilioSmsDriver)->send($this->notification(), $this->channel());

        $this->assertTrue($result->isTransientFailure());
    }

    public function test_server_error_5xx_is_transient(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response('Internal Server Error', 500),
        ]);

        $result = (new TwilioSmsDriver)->send($this->notification(), $this->channel());

        $this->assertTrue($result->isTransientFailure());
    }

    public function test_connection_timeout_is_transient(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out.');
        });

        $result = (new TwilioSmsDriver)->send($this->notification(), $this->channel());

        $this->assertTrue($result->isTransientFailure());
    }

    public function test_unmapped_4xx_error_defaults_to_permanent(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['code' => 21201, 'message' => 'no To number'], 400),
        ]);

        $result = (new TwilioSmsDriver)->send($this->notification(), $this->channel());

        $this->assertTrue($result->isPermanentFailure());
    }

    public function test_missing_credentials_fail_permanently_without_any_http_call(): void
    {
        Http::fake();

        $channel = new ResolvedChannel(
            driver: 'twilio',
            credentials: [],
            sender: 'NOTIFIKA',
            source: ResolvedChannel::SOURCE_INSTALLATION,
        );

        $result = (new TwilioSmsDriver)->send($this->notification(), $channel);

        $this->assertTrue($result->isPermanentFailure());
        Http::assertNothingSent();
    }

    public function test_missing_sender_fails_permanently_without_any_http_call(): void
    {
        Http::fake();

        $result = (new TwilioSmsDriver)->send($this->notification(), $this->channel(sender: ''));

        $this->assertTrue($result->isPermanentFailure());
        Http::assertNothingSent();
    }

    public function test_international_sender_logs_a_movitel_warning(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201),
        ]);

        Log::spy();

        (new TwilioSmsDriver)->send($this->notification(), $this->channel(sender: '+15005550006'));

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message) => $message === 'notifika.twilio_sms.international_sender_id');
    }

    public function test_alphanumeric_sender_does_not_log_a_warning(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201),
        ]);

        Log::spy();

        (new TwilioSmsDriver)->send($this->notification(), $this->channel(sender: 'NOTIFIKA'));

        Log::shouldNotHaveReceived('warning');
    }
}
