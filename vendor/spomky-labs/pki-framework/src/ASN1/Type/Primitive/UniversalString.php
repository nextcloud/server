<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Primitive;

use SpomkyLabs\Pki\ASN1\Type\PrimitiveString;
use SpomkyLabs\Pki\ASN1\Type\UniversalClass;
use function mb_strlen;

/**
 * Implements *UniversalString* type.
 *
 * Universal string is an Unicode string with UCS-4 encoding.
 */
final class UniversalString extends PrimitiveString
{
    use UniversalClass;

    private function __construct(string $string)
    {
        parent::__construct(self::TYPE_UNIVERSAL_STRING, $string);
    }

    public static function create(string $string): self
    {
        return new self($string);
    }

    protected function validateString(string $string): bool
    {
        // UCS-4 has fixed with of 4 octets (32 bits)
        if (mb_strlen($string, '8bit') % 4 !== 0) {
            return false;
        }
        return true;
    }
}
