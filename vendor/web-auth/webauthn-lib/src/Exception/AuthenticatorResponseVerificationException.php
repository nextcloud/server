<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

class AuthenticatorResponseVerificationException extends WebauthnException
{
    public static function create(string $message, ?Throwable $previous = null): self
    {
        return new self($message, $previous);
    }
}
