<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Primitive;

use SpomkyLabs\Pki\ASN1\Type\PrimitiveString;
use SpomkyLabs\Pki\ASN1\Type\UniversalClass;
use function mb_strlen;

/**
 * Implements *BMPString* type.
 *
 * BMP stands for Basic Multilingual Plane. This is generally an Unicode string with UCS-2 encoding.
 */
final class BMPString extends PrimitiveString
{
    use UniversalClass;

    private function __construct(string $string)
    {
        parent::__construct(self::TYPE_BMP_STRING, $string);
    }

    public static function create(string $string): self
    {
        return new self($string);
    }

    protected function validateString(string $string): bool
    {
        // UCS-2 has fixed with of 2 octets (16 bits)
        return mb_strlen($string, '8bit') % 2 === 0;
    }
}
