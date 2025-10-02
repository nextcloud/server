<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher;

use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\OctetString;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\SpecificAlgorithmIdentifier;
use UnexpectedValueException;

/*
RFC 2898 defines parameters as follows:

{OCTET STRING (SIZE(8)) IDENTIFIED BY des-EDE3-CBC}
 */

/**
 * Algorithm identifier for Triple-DES cipher in CBC mode.
 *
 * @see http://www.alvestrand.no/objectid/1.2.840.113549.3.7.html
 * @see http://oid-info.com/get/1.2.840.113549.3.7
 * @see https://tools.ietf.org/html/rfc2898#appendix-C
 * @see https://tools.ietf.org/html/rfc2630#section-12.4.1
 */
final class DESEDE3CBCAlgorithmIdentifier extends BlockCipherAlgorithmIdentifier
{
    /**
     * @param null|string $iv Initialization vector
     */
    private function __construct(?string $iv)
    {
        parent::__construct(self::OID_DES_EDE3_CBC, $iv);
        $this->_checkIVSize($iv);
    }

    public static function create(?string $iv = null): self
    {
        return new self($iv);
    }

    public function name(): string
    {
        return 'des-EDE3-CBC';
    }

    /**
     * @return self
     */
    public static function fromASN1Params(?UnspecifiedType $params = null): SpecificAlgorithmIdentifier
    {
        if (! isset($params)) {
            throw new UnexpectedValueException('No parameters.');
        }
        $iv = $params->asOctetString()
            ->string();
        return self::create($iv);
    }

    public function blockSize(): int
    {
        return 8;
    }

    public function keySize(): int
    {
        return 24;
    }

    public function ivSize(): int
    {
        return 8;
    }

    /**
     * @return OctetString
     */
    protected function paramsASN1(): ?Element
    {
        if (! isset($this->initializationVector)) {
            throw new LogicException('IV not set.');
        }
        return OctetString::create($this->initializationVector);
    }
}
