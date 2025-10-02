<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature;

use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use UnexpectedValueException;

/**
 * ECDSA with SHA-512 signature algorithm identifier.
 *
 * @see https://tools.ietf.org/html/rfc5758#section-3.2
 */
final class ECDSAWithSHA512AlgorithmIdentifier extends ECSignatureAlgorithmIdentifier
{
    private function __construct()
    {
        parent::__construct(self::OID_ECDSA_WITH_SHA512);
    }

    public static function create(): self
    {
        return new self();
    }

    public static function fromASN1Params(?UnspecifiedType $params = null): self
    {
        if ($params !== null) {
            throw new UnexpectedValueException('Parameters must be omitted.');
        }
        return self::create();
    }

    public function name(): string
    {
        return 'ecdsa-with-SHA512';
    }
}
