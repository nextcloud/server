<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

final class AttestationStatementVerificationException extends AttestationStatementException
{
    public static function create(string $message = 'Invalid attestation object', ?Throwable $previous = null): self
    {
        return new self($message, $previous);
    }
}
