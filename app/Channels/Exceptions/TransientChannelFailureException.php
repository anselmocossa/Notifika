<?php

namespace App\Channels\Exceptions;

use RuntimeException;

/**
 * Lançada pelo SendNotificationJob quando um driver devolve
 * SendResult::failedTransient() — é o que faz o Laravel repetir o job
 * com o backoff configurado (CA-07).
 */
class TransientChannelFailureException extends RuntimeException {}
