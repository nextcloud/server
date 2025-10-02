<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

class CertificateChainException extends MetadataServiceException
{
    /**
     * @param array<string> $untrustedCertificates
     * @param array<string> $trustedCertificates
     */
    public function __construct(
        public readonly array $untrustedCertificates,
        public readonly array $trustedCertificates,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }

    /**
     * @param array<string> $untrustedCertificates
     * @param array<string> $trustedCertificates
     */
    public static function create(
        array $untrustedCertificates,
        array $trustedCertificates,
        string $message = 'Unable to validate the certificate chain.',
        ?Throwable $previous = null
    ): self {
        return new self($untrustedCertificates, $trustedCertificates, $message, $previous);
    }
}
