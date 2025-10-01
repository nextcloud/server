<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash;

use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\SpecificAlgorithmIdentifier;

/**
 * SHA-512 algorithm identifier.
 *
 * @see http://oid-info.com/get/2.16.840.1.101.3.4.2.3
 * @see https://tools.ietf.org/html/rfc4055#section-2.1
 * @see https://tools.ietf.org/html/rfc5754#section-2.4
 */
final class SHA512AlgorithmIdentifier extends SHA2AlgorithmIdentifier
{
    private function __construct()
    {
        parent::__construct(self::OID_SHA512);
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
        $obj = new static();
        // if parameters field is present, it must be null type
        if (isset($params)) {
            $obj->_params = $params->asNull();
        }
        return $obj;
    }

    public function name(): string
    {
        return 'sha512';
    }
}
