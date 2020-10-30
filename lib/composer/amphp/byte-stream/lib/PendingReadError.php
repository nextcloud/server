<?php

namespace Amp\ByteStream;

/**
 * Thrown in case a second read operation is attempted while another read operation is still pending.
 */
final class PendingReadError extends \Error
{
    public function __construct(
        string $message = "The previous read operation must complete before read can be called again",
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
