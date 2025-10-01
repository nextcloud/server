<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * RSA with SHA-224 signature algorithm identifier.
 *
 * @see https://tools.ietf.org/html/rfc4055#section-5
 */
final class SHA224WithRSAEncryptionAlgorithmIdentifier extends RFC4055RSASignatureAlgorithmIdentifier
{
    private function __construct(null|Element $params)
    {
        parent::__construct(self::OID_SHA224_WITH_RSA_ENCRYPTION, $params);
    }

    public static function create(null|Element $params = null): self
    {
        return new self($params);
    }

    public static function fromASN1Params(?UnspecifiedType $params = null): self
    {
        // store parameters so re-encoding doesn't change
        return self::create($params?->asElement());
    }

    public function name(): string
    {
        return 'sha224WithRSAEncryption';
    }
}
