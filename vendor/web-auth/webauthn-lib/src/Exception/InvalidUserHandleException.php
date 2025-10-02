<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

final class InvalidUserHandleException extends AuthenticatorResponseVerificationException
{
    public static function create(string $message = 'Invalid user handle', ?Throwable $previous = null): self
    {
        return new self($message, $previous);
    }
}
