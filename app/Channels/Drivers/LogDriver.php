<?php

namespace App\Channels\Drivers;

use App\Channels\Contracts\ChannelDriver;
use App\Channels\ResolvedChannel;
use App\Channels\SendResult;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Não chama nenhum fornecedor — regista a mensagem no log e marca-a como
 * enviada. Serve o desenvolvimento local, as demonstrações e os testes
 * (CA-15: nenhum teste faz chamadas HTTP reais).
 */
class LogDriver implements ChannelDriver
{
    public function send(Notification $notification, ResolvedChannel $channel): SendResult
    {
        Log::info('notifika.log_driver.send', [
            'notification_id' => $notification->id,
            'channel' => $notification->channel,
            'to' => $notification->to,
            'content' => $notification->content,
        ]);

        return SendResult::sent(providerMessageId: 'log_'.$notification->id);
    }
}
