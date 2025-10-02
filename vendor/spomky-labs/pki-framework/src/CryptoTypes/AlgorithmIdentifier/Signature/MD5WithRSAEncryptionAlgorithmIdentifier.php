<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature;

use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use UnexpectedValueException;

/**
 * RSA with MD5 signature algorithm identifier.
 *
 * @see https://tools.ietf.org/html/rfc3279#section-2.2.1
 */
final class MD5WithRSAEncryptionAlgorithmIdentifier extends RFC3279RSASignatureAlgorithmIdentifier
{
    private function __construct()
    {
        parent::__construct(self::OID_MD5_WITH_RSA_ENCRYPTION);
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
        return self::create();
    }

    public function name(): string
    {
        return 'md5WithRSAEncryption';
    }
}
