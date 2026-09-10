<?php

namespace App\Channels\Contracts;

use App\Channels\ResolvedChannel;
use App\Channels\SendResult;
use App\Models\Notification;

interface ChannelDriver
{
    public function send(Notification $notification, ResolvedChannel $channel): SendResult;
}
