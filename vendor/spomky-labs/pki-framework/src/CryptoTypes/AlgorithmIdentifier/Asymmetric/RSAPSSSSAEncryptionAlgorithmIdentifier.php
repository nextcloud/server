<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\NullType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AsymmetricCryptoAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\SpecificAlgorithmIdentifier;
use UnexpectedValueException;

/*
From RFC 3447:

    When rsaEncryption is used in an AlgorithmIdentifier the
    parameters MUST be present and MUST be NULL.
 */

/**
 * Algorithm identifier for RSA encryption.
 *
 * @see http://www.oid-info.com/get/1.2.840.113549.1.1.10
 * @see https://datatracker.ietf.org/doc/html/rfc8017#section-8.1
 */
final class RSAPSSSSAEncryptionAlgorithmIdentifier extends SpecificAlgorithmIdentifier implements AsymmetricCryptoAlgorithmIdentifier
{
    private function __construct()
    {
        parent::__construct(self::OID_RSASSA_PSS_ENCRYPTION);
    }

    public static function create(): self
    {
        return new self();
    }

    public function name(): string
    {
        return 'rsassa-pss';
    }

    /**
     * @return self
     */
    public static function fromASN1Params(?UnspecifiedType $params = null): SpecificAlgorithmIdentifier
    {
        if (! isset($params)) {
            throw new UnexpectedValueException('No parameters.');
        }
        $params->asNull();
        return self::create();
    }

    /**
     * @return NullType
     */
    protected function paramsASN1(): ?Element
    {
        return NullType::create();
    }
}
