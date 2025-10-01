<?php

declare(strict_types=1);

namespace Brick\Math;

use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Common interface for arbitrary-precision rational numbers.
 *
 * @psalm-immutable
 */
abstract class BigNumber implements \JsonSerializable
{
    /**
     * The regular expression used to parse integer or decimal numbers.
     */
    private const PARSE_REGEXP_NUMERICAL =
        '/^' .
            '(?<sign>[\-\+])?' .
            '(?<integral>[0-9]+)?' .
            '(?<point>\.)?' .
            '(?<fractional>[0-9]+)?' .
            '(?:[eE](?<exponent>[\-\+]?[0-9]+))?' .
        '$/';

    /**
     * The regular expression used to parse rational numbers.
     */
    private const PARSE_REGEXP_RATIONAL =
        '/^' .
            '(?<sign>[\-\+])?' .
            '(?<numerator>[0-9]+)' .
            '\/?' .
            '(?<denominator>[0-9]+)' .
        '$/';

    /**
     * Creates a BigNumber of the given value.
     *
     * The concrete return type is dependent on the given value, with the following rules:
     *
     * - BigNumber instances are returned as is
     * - integer numbers are returned as BigInteger
     * - floating point numbers are converted to a string then parsed as such
     * - strings containing a `/` character are returned as BigRational
     * - strings containing a `.` character or using an exponential notation are returned as BigDecimal
     * - strings containing only digits with an optional leading `+` or `-` sign are returned as BigInteger
     *
     * @throws NumberFormatException   If the format of the number is not valid.
     * @throws DivisionByZeroException If the value represents a rational number with a denominator of zero.
     *
     * @psalm-pure
     */
    final public static function of(BigNumber|int|float|string $value) : static
    {
        $value = self::_of($value);

        if (static::class === BigNumber::class) {
            // https://github.com/vimeo/psalm/issues/10309
            assert($value instanceof static);

            return $value;
        }

        return static::from($value);
    }

    /**
     * @psalm-pure
     */
    private static function _of(BigNumber|int|float|string $value) : BigNumber
    {
        if ($value instanceof BigNumber) {
            return $value;
        }

        if (\is_int($value)) {
            return new BigInteger((string) $value);
        }

        if (is_float($value)) {
            $value = (string) $value;
        }

        if (str_contains($value, '/')) {
            // Rational number
            if (\preg_match(self::PARSE_REGEXP_RATIONAL, $value, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
                throw NumberFormatException::invalidFormat($value);
            }

            $sign        = $matches['sign'];
            $numerator   = $matches['numerator'];
            $denominator = $matches['denominator'];

            assert($numerator !== null);
            assert($denominator !== null);

            $numerator   = self::cleanUp($sign, $numerator);
            $denominator = self::cleanUp(null, $denominator);

            if ($denominator === '0') {
                throw DivisionByZeroException::denominatorMustNotBeZero();
            }

            return new BigRational(
                new BigInteger($numerator),
                new BigInteger($denominator),
                false
            );
        } else {
            // Integer or decimal number
            if (\preg_match(self::PARSE_REGEXP_NUMERICAL, $value, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
                throw NumberFormatException::invalidFormat($value);
            }

            $sign = $matches['sign'];
            $point = $matches['point'];
            $integral = $matches['integral'];
            $fractional = $matches['fractional'];
            $exponent = $matches['exponent'];

            if ($integral === null && $fractional === null) {
                throw NumberFormatException::invalidFormat($value);
            }

            if ($integral === null) {
                $integral = '0';
            }

            if ($point !== null || $exponent !== null) {
                $fractional = ($fractional ?? '');
                $exponent = ($exponent !== null) ? (int)$exponent : 0;

                if ($exponent === PHP_INT_MIN || $exponent === PHP_INT_MAX) {
                    throw new NumberFormatException('Exponent too large.');
                }

                $unscaledValue = self::cleanUp($sign, $integral . $fractional);

                $scale = \strlen($fractional) - $exponent;

                if ($scale < 0) {
                    if ($unscaledValue !== '0') {
                        $unscaledValue .= \str_repeat('0', -$scale);
                    }
                    $scale = 0;
                }

                return new BigDecimal($unscaledValue, $scale);
            }

            $integral = self::cleanUp($sign, $integral);

            return new BigInteger($integral);
        }
    }

    /**
     * Overridden by subclasses to convert a BigNumber to an instance of the subclass.
     *
     * @throws MathException If the value cannot be converted.
     *
     * @psalm-pure
     */
    abstract protected static function from(BigNumber $number): static;

    /**
     * Proxy method to access BigInteger's protected constructor from sibling classes.
     *
     * @internal
     * @psalm-pure
     */
    final protected function newBigInteger(string $value) : BigInteger
    {
        return new BigInteger($value);
    }

    /**
     * Proxy method to access BigDecimal's protected constructor from sibling classes.
     *
     * @internal
     * @psalm-pure
     */
    final protected function newBigDecimal(string $value, int $scale = 0) : BigDecimal
    {
        return new BigDecimal($value, $scale);
    }

    /**
     * Proxy method to access BigRational's protected constructor from sibling classes.
     *
     * @internal
     * @psalm-pure
     */
    final protected function newBigRational(BigInteger $numerator, BigInteger $denominator, bool $checkDenominator) : BigRational
    {
        return new BigRational($numerator, $denominator, $checkDenominator);
    }

    /**
     * Returns the minimum of the given values.
     *
     * @param BigNumber|int|float|string ...$values The numbers to compare. All the numbers need to be convertible
     *                                              to an instance of the class this method is called on.
     *
     * @throws \InvalidArgumentException If no values are given.
     * @throws MathException             If an argument is not valid.
     *
     * @psalm-pure
     */
    final public static function min(BigNumber|int|float|string ...$values) : static
    {
        $min = null;

        foreach ($values as $value) {
            $value = static::of($value);

            if ($min === null || $value->isLessThan($min)) {
                $min = $value;
            }
        }

        if ($min === null) {
            throw new \InvalidArgumentException(__METHOD__ . '() expects at least one value.');
        }

        return $min;
    }

    /**
     * Returns the maximum of the given values.
     *
     * @param BigNumber|int|float|string ...$values The numbers to compare. All the numbers need to be convertible
     *                                              to an instance of the class this method is called on.
     *
     * @throws \InvalidArgumentException If no values are given.
     * @throws MathException             If an argument is not valid.
     *
     * @psalm-pure
     */
    final public static function max(BigNumber|int|float|string ...$values) : static
    {
        $max = null;

        foreach ($values as $value) {
            $value = static::of($value);

            if ($max === null || $value->isGreaterThan($max)) {
                $max = $value;
            }
        }

        if ($max === null) {
            throw new \InvalidArgumentException(__METHOD__ . '() expects at least one value.');
        }

        return $max;
    }

    /**
     * Returns the sum of the given values.
     *
     * @param BigNumber|int|float|string ...$values The numbers to add. All the numbers need to be convertible
     *                                              to an instance of the class this method is called on.
     *
     * @throws \InvalidArgumentException If no values are given.
     * @throws MathException             If an argument is not valid.
     *
     * @psalm-pure
     */
    final public static function sum(BigNumber|int|float|string ...$values) : static
    {
        /** @var static|null $sum */
        $sum = null;

        foreach ($values as $value) {
            $value = static::of($value);

            $sum = $sum === null ? $value : self::add($sum, $value);
        }

        if ($sum === null) {
            throw new \InvalidArgumentException(__METHOD__ . '() expects at least one value.');
        }

        return $sum;
    }

    /**
     * Adds two BigNumber instances in the correct order to avoid a RoundingNecessaryException.
     *
     * @todo This could be better resolved by creating an abstract protected method in BigNumber, and leaving to
     *       concrete classes the responsibility to perform the addition themselves or delegate it to the given number,
     *       depending on their ability to perform the operation. This will also require a version bump because we're
     *       potentially breaking custom BigNumber implementations (if any...)
     *
     * @psalm-pure
     */
    private static function add(BigNumber $a, BigNumber $b) : BigNumber
    {
        if ($a instanceof BigRational) {
            return $a->plus($b);
        }

        if ($b instanceof BigRational) {
            return $b->plus($a);
        }

        if ($a instanceof BigDecimal) {
            return $a->plus($b);
        }

        if ($b instanceof BigDecimal) {
            return $b->plus($a);
        }

        /** @var BigInteger $a */

        return $a->plus($b);
    }

    /**
     * Removes optional leading zeros and applies sign.
     *
     * @param string|null $sign   The sign, '+' or '-', optional. Null is allowed for convenience and treated as '+'.
     * @param string      $number The number, validated as a non-empty string of digits.
     *
     * @psalm-pure
     */
    private static function cleanUp(string|null $sign, string $number) : string
    {
        $number = \ltrim($number, '0');

        if ($number === '') {
            return '0';
        }

        return $sign === '-' ? '-' . $number : $number;
    }

    /**
     * Checks if this number is equal to the given one.
     */
    final public function isEqualTo(BigNumber|int|float|string $that) : bool
    {
        return $this->compareTo($that) === 0;
    }

    /**
     * Checks if this number is strictly lower than the given one.
     */
    final public function isLessThan(BigNumber|int|float|string $that) : bool
    {
        return $this->compareTo($that) < 0;
    }

    /**
     * Checks if this number is lower than or equal to the given one.
     */
    final public function isLessThanOrEqualTo(BigNumber|int|float|string $that) : bool
    {
        return $this->compareTo($that) <= 0;
    }

    /**
     * Checks if this number is strictly greater than the given one.
     */
    final public function isGreaterThan(BigNumber|int|float|string $that) : bool
    {
        return $this->compareTo($that) > 0;
    }

    /**
     * Checks if this number is greater than or equal to the given one.
     */
    final public function isGreaterThanOrEqualTo(BigNumber|int|float|string $that) : bool
    {
        return $this->compareTo($that) >= 0;
    }

    /**
     * Checks if this number equals zero.
     */
    final public function isZero() : bool
    {
        return $this->getSign() === 0;
    }

    /**
     * Checks if this number is strictly negative.
     */
    final public function isNegative() : bool
    {
        return $this->getSign() < 0;
    }

    /**
     * Checks if this number is negative or zero.
     */
    final public function isNegativeOrZero() : bool
    {
        return $this->getSign() <= 0;
    }

    /**
     * Checks if this number is strictly positive.
     */
    final public function isPositive() : bool
    {
        return $this->getSign() > 0;
    }

    /**
     * Checks if this number is positive or zero.
     */
    final public function isPositiveOrZero() : bool
    {
        return $this->getSign() >= 0;
    }

    /**
     * Returns the sign of this number.
     *
     * @psalm-return -1|0|1
     *
     * @return int -1 if the number is negative, 0 if zero, 1 if positive.
     */
    abstract public function getSign() : int;

    /**
     * Compares this number to the given one.
     *
     * @psalm-return -1|0|1
     *
     * @return int -1 if `$this` is lower than, 0 if equal to, 1 if greater than `$that`.
     *
     * @throws MathException If the number is not valid.
     */
    abstract public function compareTo(BigNumber|int|float|string $that) : int;

    /**
     * Converts this number to a BigInteger.
     *
     * @throws RoundingNecessaryException If this number cannot be converted to a BigInteger without rounding.
     */
    abstract public function toBigInteger() : BigInteger;

    /**
     * Converts this number to a BigDecimal.
     *
     * @throws RoundingNecessaryException If this number cannot be converted to a BigDecimal without rounding.
     */
    abstract public function toBigDecimal() : BigDecimal;

    /**
     * Converts this number to a BigRational.
     */
    abstract public function toBigRational() : BigRational;

    /**
     * Converts this number to a BigDecimal with the given scale, using rounding if necessary.
     *
     * @param int          $scale        The scale of the resulting `BigDecimal`.
     * @param RoundingMode $roundingMode An optional rounding mode, defaults to UNNECESSARY.
     *
     * @throws RoundingNecessaryException If this number cannot be converted to the given scale without rounding.
     *                                    This only applies when RoundingMode::UNNECESSARY is used.
     */
    abstract public function toScale(int $scale, RoundingMode $roundingMode = RoundingMode::UNNECESSARY) : BigDecimal;

    /**
     * Returns the exact value of this number as a native integer.
     *
     * If this number cannot be converted to a native integer without losing precision, an exception is thrown.
     * Note that the acceptable range for an integer depends on the platform and differs for 32-bit and 64-bit.
     *
     * @throws MathException If this number cannot be exactly converted to a native integer.
     */
    abstract public function toInt() : int;

    /**
     * Returns an approximation of this number as a floating-point value.
     *
     * Note that this method can discard information as the precision of a floating-point value
     * is inherently limited.
     *
     * If the number is greater than the largest representable floating point number, positive infinity is returned.
     * If the number is less than the smallest representable floating point number, negative infinity is returned.
     */
    abstract public function toFloat() : float;

    /**
     * Returns a string representation of this number.
     *
     * The output of this method can be parsed by the `of()` factory method;
     * this will yield an object equal to this one, without any information loss.
     */
    abstract public function __toString() : string;

    final public function jsonSerialize() : string
    {
        return $this->__toString();
    }
}
