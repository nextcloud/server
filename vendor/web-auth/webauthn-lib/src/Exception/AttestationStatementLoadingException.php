<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

final class AttestationStatementLoadingException extends AttestationStatementException
{
    /**
     * @param array<string, mixed> $attestation
     */
    public function __construct(
        public readonly array $attestation,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }

    /**
     * @param array<string, mixed> $attestation
     */
    public static function create(
        array $attestation,
        string $message = 'Invalid attestation object',
        ?Throwable $previous = null
    ): self {
        return new self($attestation, $message, $previous);
    }
}
