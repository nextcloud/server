<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\CertificateChain;

interface CertificateChainValidator
{
    /**
     * @param string[] $untrustedCertificates
     * @param string[] $trustedCertificates
     */
    public function check(array $untrustedCertificates, array $trustedCertificates): void;
}
