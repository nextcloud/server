<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Primitive;

use SpomkyLabs\Pki\ASN1\Type\PrimitiveString;
use SpomkyLabs\Pki\ASN1\Type\UniversalClass;

/**
 * Implements *GraphicString* type.
 */
final class GraphicString extends PrimitiveString
{
    use UniversalClass;

    private function __construct(string $string)
    {
        parent::__construct(self::TYPE_GRAPHIC_STRING, $string);
    }

    public static function create(string $string): self
    {
        return new self($string);
    }

    protected function validateString(string $string): bool
    {
        // allow everything
        return true;
    }
}
