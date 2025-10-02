<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

/**
 * @final
 */
class InvalidCertificateException extends MetadataServiceException
{
    public function __construct(
        public readonly string $certificate,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }

    public static function create(string $certificate, string $message, ?Throwable $previous = null): self
    {
        return new self($certificate, $message, $previous);
    }
}
