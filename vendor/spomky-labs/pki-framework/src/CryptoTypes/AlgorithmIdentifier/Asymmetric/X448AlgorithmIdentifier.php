<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric;

use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\SpecificAlgorithmIdentifier;
use UnexpectedValueException;

/**
 * Algorithm identifier for the Diffie-Hellman operation with curve448.
 *
 * @see http://oid-info.com/get/1.3.101.111
 */
final class X448AlgorithmIdentifier extends RFC8410XAlgorithmIdentifier
{
    private function __construct()
    {
        parent::__construct(self::OID_X448);
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
        return 'id-X448';
    }
}
