<?php

declare(strict_types=1);

namespace Webauthn\Event;

/**
 * @final
 */
class BeforeCertificateChainValidation implements WebauthnEvent
{
    /**
     * @param string[] $untrustedCertificates
     */
    public function __construct(
        public readonly array $untrustedCertificates,
        public readonly string $trustedCertificate
    ) {
    }

    /**
     * @param string[] $untrustedCertificates
     */
    public static function create(array $untrustedCertificates, string $trustedCertificate): self
    {
        return new self($untrustedCertificates, $trustedCertificate);
    }
}
