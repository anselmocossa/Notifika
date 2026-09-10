<?php

namespace App\Channels;

use App\Channels\Contracts\ChannelDriver;
use App\Channels\Drivers\LogDriver;
use App\Channels\Drivers\MailDriver;
use App\Channels\Drivers\TwilioSmsDriver;
use App\Channels\Exceptions\ChannelNotConfiguredException;
use App\Models\Client;
use App\Models\Notification;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;

/**
 * Resolve o driver de um canal e envia por ele (SDD §5, CA-04).
 *
 * Ordem de resolução:
 *   1. credenciais do cliente para o canal (client_channel_credentials)
 *   2. senão, as da instalação (config/notifika.php -> .env)
 *   3. senão, ChannelNotConfiguredException (o controller devolve 422)
 */
class ChannelManager
{
    /** @var array<string, class-string<ChannelDriver>> */
    private array $drivers;

    public function __construct(
        private readonly Container $container,
        private readonly ConfigRepository $config,
        ?array $drivers = null,
    ) {
        $this->drivers = $drivers ?? [
            'mail' => MailDriver::class,
            'log' => LogDriver::class,
            'twilio' => TwilioSmsDriver::class,
        ];
    }

    public function resolve(?Client $client, string $channel): ResolvedChannel
    {
        $credential = $client?->credentialFor($channel);

        if ($credential) {
            return new ResolvedChannel(
                driver: $credential->driver,
                credentials: $credential->credentials ?? [],
                sender: $credential->sender,
                source: ResolvedChannel::SOURCE_CLIENT,
            );
        }

        $installationDriver = $this->config->get("notifika.channels.{$channel}.driver");

        if (filled($installationDriver)) {
            return new ResolvedChannel(
                driver: $installationDriver,
                credentials: (array) $this->config->get("notifika.channels.{$channel}.credentials", []),
                sender: $this->config->get("notifika.channels.{$channel}.sender"),
                source: ResolvedChannel::SOURCE_INSTALLATION,
            );
        }

        throw ChannelNotConfiguredException::forChannel($channel);
    }

    public function driverFor(string $driverName): ChannelDriver
    {
        if (! isset($this->drivers[$driverName])) {
            throw new ChannelNotConfiguredException("Driver '{$driverName}' desconhecido.");
        }

        return $this->container->make($this->drivers[$driverName]);
    }

    public function send(Notification $notification): SendResult
    {
        $resolved = $this->resolve($notification->client, $notification->channel);
        $driver = $this->driverFor($resolved->driver);

        return $driver->send($notification, $resolved);
    }
}
