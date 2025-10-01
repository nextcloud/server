<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Primitive;

use Brick\Math\BigInteger;
use InvalidArgumentException;
use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Component\Length;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Feature\ElementBase;
use SpomkyLabs\Pki\ASN1\Type\PrimitiveType;
use SpomkyLabs\Pki\ASN1\Type\UniversalClass;
use SpomkyLabs\Pki\ASN1\Util\BigInt;
use function gettype;
use function is_int;
use function is_scalar;
use function is_string;

/**
 * Implements *INTEGER* type.
 */
class Integer extends Element
{
    use UniversalClass;
    use PrimitiveType;

    /**
     * The number.
     */
    private readonly BigInt $_number;

    /**
     * @param BigInteger|int|string $number Base 10 integer
     */
    final protected function __construct(BigInteger|int|string $number, int $typeTag)
    {
        parent::__construct($typeTag);
        if (! self::validateNumber($number)) {
            $var = is_scalar($number) ? (string) $number : gettype($number);
            throw new InvalidArgumentException("'{$var}' is not a valid number.");
        }
        $this->_number = BigInt::create($number);
    }

    public static function create(BigInteger|int|string $number): static
    {
        return new static($number, self::TYPE_INTEGER);
    }

    /**
     * Get the number as a base 10.
     *
     * @return string Integer as a string
     */
    public function number(): string
    {
        return $this->_number->base10();
    }

    public function getValue(): BigInteger
    {
        return $this->_number->getValue();
    }

    /**
     * Get the number as an integer type.
     */
    public function intNumber(): int
    {
        return $this->_number->toInt();
    }

    protected function encodedAsDER(): string
    {
        return $this->_number->signedOctets();
    }

    protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): ElementBase
    {
        $idx = $offset;
        $length = Length::expectFromDER($data, $idx)->intLength();
        $bytes = mb_substr($data, $idx, $length, '8bit');
        $idx += $length;
        $num = BigInt::fromSignedOctets($bytes)->getValue();
        $offset = $idx;
        // late static binding since enumerated extends integer type
        return static::create($num);
    }

    /**
     * Test that number is valid for this context.
     */
    private static function validateNumber(mixed $num): bool
    {
        if (is_int($num)) {
            return true;
        }
        if (is_string($num) && preg_match('/-?\d+/', $num) === 1) {
            return true;
        }
        if ($num instanceof BigInteger) {
            return true;
        }
        return false;
    }
}
