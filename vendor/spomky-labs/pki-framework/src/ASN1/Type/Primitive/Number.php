<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Primitive;

use Brick\Math\BigInteger;
use InvalidArgumentException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\PrimitiveType;
use SpomkyLabs\Pki\ASN1\Type\UniversalClass;
use SpomkyLabs\Pki\ASN1\Util\BigInt;
use function gettype;
use function is_int;
use function is_scalar;
use function is_string;
use function strval;

abstract class Number extends Element
{
    use UniversalClass;
    use PrimitiveType;

    /**
     * The number.
     */
    private readonly BigInt $number;

    /**
     * @param BigInteger|int|string $number Base 10 integer
     */
    protected function __construct(int $tag, BigInteger|int|string $number)
    {
        parent::__construct($tag);
        if (! self::validateNumber($number)) {
            $var = is_scalar($number) ? strval($number) : gettype($number);
            throw new InvalidArgumentException(sprintf('"%s" is not a valid number.', $var));
        }
        $this->number = BigInt::create($number);
    }

    abstract public static function create(BigInteger|int|string $number): self;

    /**
     * Get the number as a base 10.
     *
     * @return string Integer as a string
     */
    public function number(): string
    {
        return $this->number->base10();
    }

    public function getValue(): BigInteger
    {
        return $this->number->getValue();
    }

    /**
     * Get the number as an integer type.
     */
    public function intNumber(): int
    {
        return $this->number->toInt();
    }

    protected function encodedAsDER(): string
    {
        return $this->number->signedOctets();
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
