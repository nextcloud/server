<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature;

use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use UnexpectedValueException;

/**
 * RSA with SHA-1 signature algorithm identifier.
 *
 * @see https://tools.ietf.org/html/rfc3279#section-2.2.1
 */
final class SHA1WithRSAEncryptionAlgorithmIdentifier extends RFC3279RSASignatureAlgorithmIdentifier
{
    private function __construct()
    {
        parent::__construct(self::OID_SHA1_WITH_RSA_ENCRYPTION);
    }

    public static function create(): self
    {
        return new self();
    }

    public static function fromASN1Params(?UnspecifiedType $params = null): self
    {
        if (! isset($params)) {
            throw new UnexpectedValueException('No parameters.');
        }
        $params->asNull();
        return self::create();
    }

    public function name(): string
    {
        return 'sha1-with-rsa-signature';
    }
}
