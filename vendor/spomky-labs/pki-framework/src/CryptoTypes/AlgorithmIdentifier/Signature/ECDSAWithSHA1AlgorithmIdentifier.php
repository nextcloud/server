<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature;

use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use UnexpectedValueException;

/**
 * ECDSA with SHA-1 signature algorithm identifier.
 *
 * @see https://tools.ietf.org/html/rfc3279#section-2.2.3
 */
final class ECDSAWithSHA1AlgorithmIdentifier extends ECSignatureAlgorithmIdentifier
{
    private function __construct()
    {
        parent::__construct(self::OID_ECDSA_WITH_SHA1);
    }

    public static function create(): self
    {
        return new self();
    }

    public function name(): string
    {
        return 'ecdsa-with-SHA1';
    }

    public static function fromASN1Params(?UnspecifiedType $params = null): self
    {
        if ($params !== null) {
            throw new UnexpectedValueException('Parameters must be omitted.');
        }
        return self::create();
    }
}
