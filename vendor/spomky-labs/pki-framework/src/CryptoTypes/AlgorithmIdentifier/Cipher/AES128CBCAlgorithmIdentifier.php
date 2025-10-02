<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher;

use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\SpecificAlgorithmIdentifier;
use UnexpectedValueException;

/**
 * Algorithm identifier for AES with 128-bit key in CBC mode.
 *
 * @see https://tools.ietf.org/html/rfc3565.html#section-4.1
 * @see http://www.alvestrand.no/objectid/2.16.840.1.101.3.4.1.2.html
 * @see http://www.oid-info.com/get/2.16.840.1.101.3.4.1.2
 */
final class AES128CBCAlgorithmIdentifier extends AESCBCAlgorithmIdentifier
{
    /**
     * @param string $iv Initialization vector
     */
    protected function __construct(string $iv)
    {
        parent::__construct(self::OID_AES_128_CBC, $iv);
    }

    public static function create(string $iv): self
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
        return self::create($iv);
    }

    public function name(): string
    {
        return 'aes128-CBC';
    }

    public function keySize(): int
    {
        return 16;
    }
}
