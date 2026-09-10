<?php

namespace App\Channels;

/**
 * O resultado da resolução do ChannelManager (CA-04): qual driver usar,
 * com que credenciais e que remetente, e se vieram do cliente ou da
 * instalação.
 */
final class ResolvedChannel
{
    public const SOURCE_CLIENT = 'client';

    public const SOURCE_INSTALLATION = 'installation';

    public function __construct(
        public readonly string $driver,
        public readonly array $credentials,
        public readonly ?string $sender,
        public readonly string $source,
    ) {}
}
