<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * HMAC with SHA-224 algorithm identifier.
 *
 * @see https://tools.ietf.org/html/rfc4231#section-3.1
 */
final class HMACWithSHA224AlgorithmIdentifier extends RFC4231HMACAlgorithmIdentifier
{
    private function __construct(?Element $params)
    {
        parent::__construct(self::OID_HMAC_WITH_SHA224, $params);
    }

    public static function create(?Element $params = null): self
    {
        return new self($params);
    }

    public static function fromASN1Params(?UnspecifiedType $params = null): self
    {
        /*
         * RFC 4231 states that the "parameter" component SHOULD be present
         * but have type NULL.
         */
        return self::create($params?->asNull());
    }

    public function name(): string
    {
        return 'hmacWithSHA224';
    }
}
