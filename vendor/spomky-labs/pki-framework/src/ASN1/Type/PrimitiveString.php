<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type;

use InvalidArgumentException;
use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Component\Length;
use SpomkyLabs\Pki\ASN1\Exception\DecodeException;

/**
 * Base class for primitive strings.
 *
 * Used by types that don't require special processing of the encoded string data.
 *
 * @internal
 */
abstract class PrimitiveString extends BaseString
{
    use PrimitiveType;

    abstract public static function create(string $string): self;

    protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): static
    {
        $idx = $offset;
        if (! $identifier->isPrimitive()) {
            throw new DecodeException('DER encoded string must be primitive.');
        }
        $length = Length::expectFromDER($data, $idx)->intLength();
        $str = $length === 0 ? '' : mb_substr($data, $idx, $length, '8bit');
        $offset = $idx + $length;
        try {
            return static::create($str);
        } catch (InvalidArgumentException $e) {
            throw new DecodeException($e->getMessage(), 0, $e);
        }
    }
}
