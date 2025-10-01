<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

final class CounterException extends WebauthnException
{
    public function __construct(
        public int $currentCounter,
        public int $authenticatorCounter,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }

    public static function create(
        int $currentCounter,
        int $authenticatorCounter,
        string $message,
        ?Throwable $previous = null
    ): self {
        return new self($currentCounter, $authenticatorCounter, $message, $previous);
    }
}
