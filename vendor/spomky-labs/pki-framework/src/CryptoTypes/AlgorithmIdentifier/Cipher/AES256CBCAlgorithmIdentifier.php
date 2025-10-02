<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher;

use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\SpecificAlgorithmIdentifier;
use UnexpectedValueException;

/**
 * Algorithm identifier for AES with 256-bit key in CBC mode.
 *
 * @see https://tools.ietf.org/html/rfc3565.html#section-4.1
 * @see http://www.alvestrand.no/objectid/2.16.840.1.101.3.4.1.42.html
 * @see http://www.oid-info.com/get/2.16.840.1.101.3.4.1.42
 */
final class AES256CBCAlgorithmIdentifier extends AESCBCAlgorithmIdentifier
{
    /**
     * @param null|string $iv Initialization vector
     */
    protected function __construct(?string $iv = null)
    {
        parent::__construct(self::OID_AES_256_CBC, $iv);
    }

    public static function create(?string $iv = null): self
    {
        return new self($iv);
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
        return new static($iv);
    }

    public function name(): string
    {
        return 'aes256-CBC';
    }

    public function keySize(): int
    {
        return 32;
    }
}
