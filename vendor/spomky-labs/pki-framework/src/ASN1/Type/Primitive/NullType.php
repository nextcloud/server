<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Primitive;

use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Component\Length;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Exception\DecodeException;
use SpomkyLabs\Pki\ASN1\Feature\ElementBase;
use SpomkyLabs\Pki\ASN1\Type\PrimitiveType;
use SpomkyLabs\Pki\ASN1\Type\UniversalClass;

/**
 * Implements *NULL* type.
 */
final class NullType extends Element
{
    use UniversalClass;
    use PrimitiveType;

    private function __construct()
    {
        parent::__construct(self::TYPE_NULL);
    }

    public static function create(): self
    {
        return new self();
    }

    protected function encodedAsDER(): string
    {
        return '';
    }

    protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): ElementBase
    {
        $idx = $offset;
        if (! $identifier->isPrimitive()) {
            throw new DecodeException('Null value must be primitive.');
        }
        // null type has always zero length
        Length::expectFromDER($data, $idx, 0);
        $offset = $idx;
        return self::create();
    }
}
