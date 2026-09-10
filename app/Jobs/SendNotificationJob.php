<?php

namespace App\Jobs;

use Throwable;
use App\Channels\EmailChannel;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 600];

    public function __construct(
        public Notification $notification
    ) {
        $this->queue = 'notifications';
    }

    public function handle(EmailChannel $emailChannel): void
    {
        if ($this->notification->status === 'sent') {
            return;
        }

        $emailChannel->send($this->notification);
    }

    public function failed(Throwable $exception): void
    {
        $this->notification->markAsFailed($exception->getMessage());
    }
}
