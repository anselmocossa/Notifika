<?php

namespace App\Channels\Drivers;

use App\Channels\Contracts\ChannelDriver;
use App\Channels\ResolvedChannel;
use App\Channels\SendResult;
use App\Models\Notification;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Canal 'email' — é o EmailChannel de sempre, agora atrás do contrato
 * ChannelDriver. Usa sempre o mailer configurado na instalação (config/mail.php);
 * não há credenciais por cliente para e-mail nesta leva.
 */
class MailDriver implements ChannelDriver
{
    public function send(Notification $notification, ResolvedChannel $channel): SendResult
    {
        try {
            Mail::raw($notification->content, function (Message $message) use ($notification) {
                $message->to($notification->to)
                    ->subject($notification->subject ?? 'Notificação');
            });

            return SendResult::sent();
        } catch (Throwable $e) {
            // Falhas de envio de e-mail (SMTP em baixo, timeout, etc.) são,
            // por regra, transitórias: voltam a tentar-se com backoff.
            return SendResult::failedTransient($e->getMessage());
        }
    }
}
