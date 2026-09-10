<?php

namespace App\Channels;

use Exception;
use App\Models\Notification;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class EmailChannel
{
    public function send(Notification $notification): void
    {
        $notification->update([
            'status' => 'processing',
            'attempts' => $notification->attempts + 1,
        ]);

        try {
            Mail::raw($notification->content, function (Message $message) use ($notification) {
                $message->to($notification->to)
                    ->subject($notification->subject ?? 'Notificação');
            });

            $notification->markAsSent();
        } catch (Exception $e) {
            if ($notification->attempts >= $notification->max_attempts) {
                $notification->markAsFailed($e->getMessage());
            } else {
                $notification->update([
                    'status' => 'queued',
                    'error_message' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }
}
