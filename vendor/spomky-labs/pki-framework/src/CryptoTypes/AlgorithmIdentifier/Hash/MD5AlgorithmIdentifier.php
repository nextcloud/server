<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\NullType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\HashAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\SpecificAlgorithmIdentifier;

/*
From RFC 1321 - 1. Executive Summary

    In the X.509 type AlgorithmIdentifier, the parameters for MD5
    should have type NULL.

From RFC 3370 - 2.2 MD5

    The AlgorithmIdentifier parameters field MUST be present, and the
    parameters field MUST contain NULL.  Implementations MAY accept the
    MD5 AlgorithmIdentifiers with absent parameters as well as NULL
    parameters.
 */

/**
 * MD5 algorithm identifier.
 *
 * @see http://oid-info.com/get/1.2.840.113549.2.5
 * @see https://tools.ietf.org/html/rfc1321#section-1
 * @see https://tools.ietf.org/html/rfc3370#section-2.2
 */
final class MD5AlgorithmIdentifier extends SpecificAlgorithmIdentifier implements HashAlgorithmIdentifier
{
    /**
     * Parameters.
     */
    private ?NullType $params;

    private function __construct()
    {
        parent::__construct(self::OID_MD5);
        $this->params = NullType::create();
    }

    public static function create(): self
    {
        return new self();
    }

    public function name(): string
    {
        return 'md5';
    }

    public static function fromASN1Params(?UnspecifiedType $params = null): static
    {
        $obj = static::create();
        // if parameters field is present, it must be null type
        if (isset($params)) {
            $obj->params = $params->asNull();
        }
        return $obj;
    }

    /**
     * @return null|NullType
     */
    protected function paramsASN1(): ?Element
    {
        return $this->params;
    }
}
