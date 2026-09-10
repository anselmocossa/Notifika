<?php

namespace Tests\Unit\Channels;

use App\Channels\ChannelManager;
use App\Channels\Drivers\LogDriver;
use App\Channels\Drivers\TwilioSmsDriver;
use App\Channels\Exceptions\ChannelNotConfiguredException;
use App\Channels\ResolvedChannel;
use App\Models\Client;
use App\Models\ClientChannelCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_client_credentials_first(): void
    {
        $client = Client::factory()->create();
        ClientChannelCredential::create([
            'client_id' => $client->id,
            'channel' => 'sms',
            'driver' => 'twilio',
            'credentials' => ['account_sid' => 'ACxxx', 'auth_token' => 'secret'],
            'sender' => 'NOTIFIKA',
            'is_active' => true,
        ]);

        // Instalação também tem um driver configurado, para provar que o
        // cliente ganha (CA-04: cliente -> instalação -> 422).
        config()->set('notifika.channels.sms.driver', 'log');

        $resolved = app(ChannelManager::class)->resolve($client, 'sms');

        $this->assertSame('twilio', $resolved->driver);
        $this->assertSame('NOTIFIKA', $resolved->sender);
        $this->assertSame(ResolvedChannel::SOURCE_CLIENT, $resolved->source);
    }

    public function test_falls_back_to_installation_when_client_has_no_credentials(): void
    {
        $client = Client::factory()->create();

        config()->set('notifika.channels.sms.driver', 'log');
        config()->set('notifika.channels.sms.sender', 'INSTALL');
        config()->set('notifika.channels.sms.credentials', []);

        $resolved = app(ChannelManager::class)->resolve($client, 'sms');

        $this->assertSame('log', $resolved->driver);
        $this->assertSame('INSTALL', $resolved->sender);
        $this->assertSame(ResolvedChannel::SOURCE_INSTALLATION, $resolved->source);
    }

    public function test_throws_when_nothing_is_configured(): void
    {
        $client = Client::factory()->create();

        config()->set('notifika.channels.sms.driver', null);

        $this->expectException(ChannelNotConfiguredException::class);

        app(ChannelManager::class)->resolve($client, 'sms');
    }

    public function test_ignores_inactive_client_credentials(): void
    {
        $client = Client::factory()->create();
        ClientChannelCredential::create([
            'client_id' => $client->id,
            'channel' => 'sms',
            'driver' => 'twilio',
            'credentials' => ['account_sid' => 'ACxxx', 'auth_token' => 'secret'],
            'sender' => 'NOTIFIKA',
            'is_active' => false,
        ]);

        config()->set('notifika.channels.sms.driver', 'log');
        config()->set('notifika.channels.sms.sender', 'INSTALL');

        $resolved = app(ChannelManager::class)->resolve($client, 'sms');

        $this->assertSame(ResolvedChannel::SOURCE_INSTALLATION, $resolved->source);
    }

    public function test_driver_for_resolves_the_registered_classes(): void
    {
        $manager = app(ChannelManager::class);

        $this->assertInstanceOf(TwilioSmsDriver::class, $manager->driverFor('twilio'));
        $this->assertInstanceOf(LogDriver::class, $manager->driverFor('log'));
    }

    public function test_driver_for_throws_for_unknown_driver_name(): void
    {
        $this->expectException(ChannelNotConfiguredException::class);

        app(ChannelManager::class)->driverFor('driver-que-nao-existe');
    }
}
