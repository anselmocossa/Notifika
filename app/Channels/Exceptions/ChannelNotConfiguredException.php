<?php

namespace App\Channels\Exceptions;

use RuntimeException;

/**
 * Nenhuma credencial de cliente nem da instalação resolve este canal
 * (CA-04). O controller da API apanha esta excepção e devolve 422.
 */
class ChannelNotConfiguredException extends RuntimeException
{
    public static function forChannel(string $channel): self
    {
        return new self("Canal '{$channel}' não configurado.");
    }
}
