<?php

namespace App\Jobs;

use App\Channels\ChannelManager;
use App\Channels\Exceptions\ChannelNotConfiguredException;
use App\Channels\Exceptions\TransientChannelFailureException;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(
        public Notification $notification
    ) {
        $this->queue = config('notifika.queue', 'notifications');
    }

    public function handle(ChannelManager $channelManager): void
    {
        if ($this->notification->status === 'sent') {
            return;
        }

        try {
            $resolved = $channelManager->resolve($this->notification->client, $this->notification->channel);
        } catch (ChannelNotConfiguredException $e) {
            // Não devia acontecer aqui — o controller já valida antes de
            // despachar o job — mas se acontecer é configuração: permanente.
            $this->notification->markAsFailed($e->getMessage());

            return;
        }

        $this->notification->update([
            'status' => 'processing',
            'attempts' => $this->notification->attempts + 1,
            'driver' => $resolved->driver,
        ]);

        $result = $channelManager->driverFor($resolved->driver)->send($this->notification, $resolved);

        if ($result->isSent()) {
            $this->notification->markAsSent(array_filter([
                'provider_message_id' => $result->providerMessageId,
                'cost' => $result->cost,
                'cost_currency' => $result->costCurrency,
            ], fn ($value) => $value !== null));

            return;
        }

        if ($result->isPermanentFailure()) {
            $this->notification->markAsFailed($result->errorMessage ?? 'Falha permanente.');

            return;
        }

        // Transitório: volta a 'queued' e relança para o Laravel repetir com backoff (CA-07).
        $this->notification->update([
            'status' => 'queued',
            'error_message' => $result->errorMessage,
        ]);

        throw new TransientChannelFailureException($result->errorMessage ?? 'Falha transitória.');
    }

    public function failed(Throwable $exception): void
    {
        $this->notification->markAsFailed($exception->getMessage());
    }
}
