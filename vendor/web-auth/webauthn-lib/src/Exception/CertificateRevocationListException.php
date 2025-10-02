<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

/**
 * @final
 */
class CertificateRevocationListException extends MetadataServiceException
{
    public function __construct(
        public readonly string $url,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }

    public static function create(string $url, string $message = 'Invalid CRL.', ?Throwable $previous = null): self
    {
        return new self($url, $message, $previous);
    }
}
