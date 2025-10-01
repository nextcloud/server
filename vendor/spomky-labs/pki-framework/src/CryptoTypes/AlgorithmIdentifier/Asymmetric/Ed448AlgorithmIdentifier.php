<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric;

use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\SpecificAlgorithmIdentifier;
use UnexpectedValueException;

/**
 * Algorithm identifier for the Edwards-curve Digital Signature Algorithm (EdDSA) with curve448.
 *
 * Same algorithm identifier is used for public and private keys as well as for signatures.
 *
 * @see http://oid-info.com/get/1.3.101.113
 * @see https://tools.ietf.org/html/rfc8420#appendix-A.2
 */
final class Ed448AlgorithmIdentifier extends RFC8410EdAlgorithmIdentifier
{
    protected function __construct()
    {
        parent::__construct(self::OID_ED448);
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * @return self
     */
    public static function fromASN1Params(?UnspecifiedType $params = null): SpecificAlgorithmIdentifier
    {
        if ($params !== null) {
            throw new UnexpectedValueException('Parameters must be absent.');
        }
        return self::create();
    }

    public function name(): string
    {
        return 'id-Ed448';
    }

    public function supportsKeyAlgorithm(AlgorithmIdentifier $algo): bool
    {
        return $algo->oid() === self::OID_ED448;
    }
}
