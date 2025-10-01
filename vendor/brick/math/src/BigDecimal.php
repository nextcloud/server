<?php

declare(strict_types=1);

namespace Brick\Math;

use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeNumberException;
use Brick\Math\Internal\Calculator;

/**
 * Immutable, arbitrary-precision signed decimal numbers.
 *
 * @psalm-immutable
 */
final class BigDecimal extends BigNumber
{
    /**
     * The unscaled value of this decimal number.
     *
     * This is a string of digits with an optional leading minus sign.
     * No leading zero must be present.
     * No leading minus sign must be present if the value is 0.
     */
    private readonly string $value;

    /**
     * The scale (number of digits after the decimal point) of this decimal number.
     *
     * This must be zero or more.
     */
    private readonly int $scale;

    /**
     * Protected constructor. Use a factory method to obtain an instance.
     *
     * @param string $value The unscaled value, validated.
     * @param int    $scale The scale, validated.
     */
    protected function __construct(string $value, int $scale = 0)
    {
        $this->value = $value;
        $this->scale = $scale;
    }

    /**
     * @psalm-pure
     */
    protected static function from(BigNumber $number): static
    {
        return $number->toBigDecimal();
    }

    /**
     * Creates a BigDecimal from an unscaled value and a scale.
     *
     * Example: `(12345, 3)` will result in the BigDecimal `12.345`.
     *
     * @param BigNumber|int|float|string $value The unscaled value. Must be convertible to a BigInteger.
     * @param int                        $scale The scale of the number, positive or zero.
     *
     * @throws \InvalidArgumentException If the scale is negative.
     *
     * @psalm-pure
     */
    public static function ofUnscaledValue(BigNumber|int|float|string $value, int $scale = 0) : BigDecimal
    {
        if ($scale < 0) {
            throw new \InvalidArgumentException('The scale cannot be negative.');
        }

        return new BigDecimal((string) BigInteger::of($value), $scale);
    }

    /**
     * Returns a BigDecimal representing zero, with a scale of zero.
     *
     * @psalm-pure
     */
    public static function zero() : BigDecimal
    {
        /**
         * @psalm-suppress ImpureStaticVariable
         * @var BigDecimal|null $zero
         */
        static $zero;

        if ($zero === null) {
            $zero = new BigDecimal('0');
        }

        return $zero;
    }

    /**
     * Returns a BigDecimal representing one, with a scale of zero.
     *
     * @psalm-pure
     */
    public static function one() : BigDecimal
    {
        /**
         * @psalm-suppress ImpureStaticVariable
         * @var BigDecimal|null $one
         */
        static $one;

        if ($one === null) {
            $one = new BigDecimal('1');
        }

        return $one;
    }

    /**
     * Returns a BigDecimal representing ten, with a scale of zero.
     *
     * @psalm-pure
     */
    public static function ten() : BigDecimal
    {
        /**
         * @psalm-suppress ImpureStaticVariable
         * @var BigDecimal|null $ten
         */
        static $ten;

        if ($ten === null) {
            $ten = new BigDecimal('10');
        }

        return $ten;
    }

    /**
     * Returns the sum of this number and the given one.
     *
     * The result has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|int|float|string $that The number to add. Must be convertible to a BigDecimal.
     *
     * @throws MathException If the number is not valid, or is not convertible to a BigDecimal.
     */
    public function plus(BigNumber|int|float|string $that) : BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->value === '0' && $that->scale <= $this->scale) {
            return $this;
        }

        if ($this->value === '0' && $this->scale <= $that->scale) {
            return $that;
        }

        [$a, $b] = $this->scaleValues($this, $that);

        $value = Calculator::get()->add($a, $b);
        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns the difference of this number and the given one.
     *
     * The result has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|int|float|string $that The number to subtract. Must be convertible to a BigDecimal.
     *
     * @throws MathException If the number is not valid, or is not convertible to a BigDecimal.
     */
    public function minus(BigNumber|int|float|string $that) : BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->value === '0' && $that->scale <= $this->scale) {
            return $this;
        }

        [$a, $b] = $this->scaleValues($this, $that);

        $value = Calculator::get()->sub($a, $b);
        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns the product of this number and the given one.
     *
     * The result has a scale of `$this->scale + $that->scale`.
     *
     * @param BigNumber|int|float|string $that The multiplier. Must be convertible to a BigDecimal.
     *
     * @throws MathException If the multiplier is not a valid number, or is not convertible to a BigDecimal.
     */
    public function multipliedBy(BigNumber|int|float|string $that) : BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->value === '1' && $that->scale === 0) {
            return $this;
        }

        if ($this->value === '1' && $this->scale === 0) {
            return $that;
        }

        $value = Calculator::get()->mul($this->value, $that->value);
        $scale = $this->scale + $that->scale;

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns the result of the division of this number by the given one, at the given scale.
     *
     * @param BigNumber|int|float|string $that         The divisor.
     * @param int|null                   $scale        The desired scale, or null to use the scale of this number.
     * @param RoundingMode               $roundingMode An optional rounding mode, defaults to UNNECESSARY.
     *
     * @throws \InvalidArgumentException If the scale or rounding mode is invalid.
     * @throws MathException             If the number is invalid, is zero, or rounding was necessary.
     */
    public function dividedBy(BigNumber|int|float|string $that, ?int $scale = null, RoundingMode $roundingMode = RoundingMode::UNNECESSARY) : BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        if ($scale === null) {
            $scale = $this->scale;
        } elseif ($scale < 0) {
            throw new \InvalidArgumentException('Scale cannot be negative.');
        }

        if ($that->value === '1' && $that->scale === 0 && $scale === $this->scale) {
            return $this;
        }

        $p = $this->valueWithMinScale($that->scale + $scale);
        $q = $that->valueWithMinScale($this->scale - $scale);

        $result = Calculator::get()->divRound($p, $q, $roundingMode);

        return new BigDecimal($result, $scale);
    }

    /**
     * Returns the exact result of the division of this number by the given one.
     *
     * The scale of the result is automatically calculated to fit all the fraction digits.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @throws MathException If the divisor is not a valid number, is not convertible to a BigDecimal, is zero,
     *                       or the result yields an infinite number of digits.
     */
    public function exactlyDividedBy(BigNumber|int|float|string $that) : BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        [, $b] = $this->scaleValues($this, $that);

        $d = \rtrim($b, '0');
        $scale = \strlen($b) - \strlen($d);

        $calculator = Calculator::get();

        foreach ([5, 2] as $prime) {
            for (;;) {
                $lastDigit = (int) $d[-1];

                if ($lastDigit % $prime !== 0) {
                    break;
                }

                $d = $calculator->divQ($d, (string) $prime);
                $scale++;
            }
        }

        return $this->dividedBy($that, $scale)->stripTrailingZeros();
    }

    /**
     * Returns this number exponentiated to the given value.
     *
     * The result has a scale of `$this->scale * $exponent`.
     *
     * @throws \InvalidArgumentException If the exponent is not in the range 0 to 1,000,000.
     */
    public function power(int $exponent) : BigDecimal
    {
        if ($exponent === 0) {
            return BigDecimal::one();
        }

        if ($exponent === 1) {
            return $this;
        }

        if ($exponent < 0 || $exponent > Calculator::MAX_POWER) {
            throw new \InvalidArgumentException(\sprintf(
                'The exponent %d is not in the range 0 to %d.',
                $exponent,
                Calculator::MAX_POWER
            ));
        }

        return new BigDecimal(Calculator::get()->pow($this->value, $exponent), $this->scale * $exponent);
    }

    /**
     * Returns the quotient of the division of this number by the given one.
     *
     * The quotient has a scale of `0`.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @throws MathException If the divisor is not a valid decimal number, or is zero.
     */
    public function quotient(BigNumber|int|float|string $that) : BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $p = $this->valueWithMinScale($that->scale);
        $q = $that->valueWithMinScale($this->scale);

        $quotient = Calculator::get()->divQ($p, $q);

        return new BigDecimal($quotient, 0);
    }

    /**
     * Returns the remainder of the division of this number by the given one.
     *
     * The remainder has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @throws MathException If the divisor is not a valid decimal number, or is zero.
     */
    public function remainder(BigNumber|int|float|string $that) : BigDecimal
    {
        $that = BigDecimal::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $p = $this->valueWithMinScale($that->scale);
        $q = $that->valueWithMinScale($this->scale);

        $remainder = Calculator::get()->divR($p, $q);

        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        return new BigDecimal($remainder, $scale);
    }

    /**
     * Returns the quotient and remainder of the division of this number by the given one.
     *
     * The quotient has a scale of `0`, and the remainder has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal[] An array containing the quotient and the remainder.
     *
     * @psalm-return array{BigDecimal, BigDecimal}
     *
     * @throws MathException If the divisor is not a valid decimal number, or is zero.
     */
    public function quotientAndRemainder(BigNumber|int|float|string $that) : array
    {
        $that = BigDecimal::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $p = $this->valueWithMinScale($that->scale);
        $q = $that->valueWithMinScale($this->scale);

        [$quotient, $remainder] = Calculator::get()->divQR($p, $q);

        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        $quotient = new BigDecimal($quotient, 0);
        $remainder = new BigDecimal($remainder, $scale);

        return [$quotient, $remainder];
    }

    /**
     * Returns the square root of this number, rounded down to the given number of decimals.
     *
     * @throws \InvalidArgumentException If the scale is negative.
     * @throws NegativeNumberException If this number is negative.
     */
    public function sqrt(int $scale) : BigDecimal
    {
        if ($scale < 0) {
            throw new \InvalidArgumentException('Scale cannot be negative.');
        }

        if ($this->value === '0') {
            return new BigDecimal('0', $scale);
        }

        if ($this->value[0] === '-') {
            throw new NegativeNumberException('Cannot calculate the square root of a negative number.');
        }

        $value = $this->value;
        $addDigits = 2 * $scale - $this->scale;

        if ($addDigits > 0) {
            // add zeros
            $value .= \str_repeat('0', $addDigits);
        } elseif ($addDigits < 0) {
            // trim digits
            if (-$addDigits >= \strlen($this->value)) {
                // requesting a scale too low, will always yield a zero result
                return new BigDecimal('0', $scale);
            }

            $value = \substr($value, 0, $addDigits);
        }

        $value = Calculator::get()->sqrt($value);

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns a copy of this BigDecimal with the decimal point moved $n places to the left.
     */
    public function withPointMovedLeft(int $n) : BigDecimal
    {
        if ($n === 0) {
            return $this;
        }

        if ($n < 0) {
            return $this->withPointMovedRight(-$n);
        }

        return new BigDecimal($this->value, $this->scale + $n);
    }

    /**
     * Returns a copy of this BigDecimal with the decimal point moved $n places to the right.
     */
    public function withPointMovedRight(int $n) : BigDecimal
    {
        if ($n === 0) {
            return $this;
        }

        if ($n < 0) {
            return $this->withPointMovedLeft(-$n);
        }

        $value = $this->value;
        $scale = $this->scale - $n;

        if ($scale < 0) {
            if ($value !== '0') {
                $value .= \str_repeat('0', -$scale);
            }
            $scale = 0;
        }

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns a copy of this BigDecimal with any trailing zeros removed from the fractional part.
     */
    public function stripTrailingZeros() : BigDecimal
    {
        if ($this->scale === 0) {
            return $this;
        }

        $trimmedValue = \rtrim($this->value, '0');

        if ($trimmedValue === '') {
            return BigDecimal::zero();
        }

        $trimmableZeros = \strlen($this->value) - \strlen($trimmedValue);

        if ($trimmableZeros === 0) {
            return $this;
        }

        if ($trimmableZeros > $this->scale) {
            $trimmableZeros = $this->scale;
        }

        $value = \substr($this->value, 0, -$trimmableZeros);
        $scale = $this->scale - $trimmableZeros;

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns the absolute value of this number.
     */
    public function abs() : BigDecimal
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    /**
     * Returns the negated value of this number.
     */
    public function negated() : BigDecimal
    {
        return new BigDecimal(Calculator::get()->neg($this->value), $this->scale);
    }

    public function compareTo(BigNumber|int|float|string $that) : int
    {
        $that = BigNumber::of($that);

        if ($that instanceof BigInteger) {
            $that = $that->toBigDecimal();
        }

        if ($that instanceof BigDecimal) {
            [$a, $b] = $this->scaleValues($this, $that);

            return Calculator::get()->cmp($a, $b);
        }

        return - $that->compareTo($this);
    }

    public function getSign() : int
    {
        return ($this->value === '0') ? 0 : (($this->value[0] === '-') ? -1 : 1);
    }

    public function getUnscaledValue() : BigInteger
    {
        return self::newBigInteger($this->value);
    }

    public function getScale() : int
    {
        return $this->scale;
    }

    /**
     * Returns a string representing the integral part of this decimal number.
     *
     * Example: `-123.456` => `-123`.
     */
    public function getIntegralPart() : string
    {
        if ($this->scale === 0) {
            return $this->value;
        }

        $value = $this->getUnscaledValueWithLeadingZeros();

        return \substr($value, 0, -$this->scale);
    }

    /**
     * Returns a string representing the fractional part of this decimal number.
     *
     * If the scale is zero, an empty string is returned.
     *
     * Examples: `-123.456` => '456', `123` => ''.
     */
    public function getFractionalPart() : string
    {
        if ($this->scale === 0) {
            return '';
        }

        $value = $this->getUnscaledValueWithLeadingZeros();

        return \substr($value, -$this->scale);
    }

    /**
     * Returns whether this decimal number has a non-zero fractional part.
     */
    public function hasNonZeroFractionalPart() : bool
    {
        return $this->getFractionalPart() !== \str_repeat('0', $this->scale);
    }

    public function toBigInteger() : BigInteger
    {
        $zeroScaleDecimal = $this->scale === 0 ? $this : $this->dividedBy(1, 0);

        return self::newBigInteger($zeroScaleDecimal->value);
    }

    public function toBigDecimal() : BigDecimal
    {
        return $this;
    }

    public function toBigRational() : BigRational
    {
        $numerator = self::newBigInteger($this->value);
        $denominator = self::newBigInteger('1' . \str_repeat('0', $this->scale));

        return self::newBigRational($numerator, $denominator, false);
    }

    public function toScale(int $scale, RoundingMode $roundingMode = RoundingMode::UNNECESSARY) : BigDecimal
    {
        if ($scale === $this->scale) {
            return $this;
        }

        return $this->dividedBy(BigDecimal::one(), $scale, $roundingMode);
    }

    public function toInt() : int
    {
        return $this->toBigInteger()->toInt();
    }

    public function toFloat() : float
    {
        return (float) (string) $this;
    }

    public function __toString() : string
    {
        if ($this->scale === 0) {
            return $this->value;
        }

        $value = $this->getUnscaledValueWithLeadingZeros();

        return \substr($value, 0, -$this->scale) . '.' . \substr($value, -$this->scale);
    }

    /**
     * This method is required for serializing the object and SHOULD NOT be accessed directly.
     *
     * @internal
     *
     * @return array{value: string, scale: int}
     */
    public function __serialize(): array
    {
        return ['value' => $this->value, 'scale' => $this->scale];
    }

    /**
     * This method is only here to allow unserializing the object and cannot be accessed directly.
     *
     * @internal
     * @psalm-suppress RedundantPropertyInitializationCheck
     *
     * @param array{value: string, scale: int} $data
     *
     * @throws \LogicException
     */
    public function __unserialize(array $data): void
    {
        if (isset($this->value)) {
            throw new \LogicException('__unserialize() is an internal function, it must not be called directly.');
        }

        $this->value = $data['value'];
        $this->scale = $data['scale'];
    }

    /**
     * Puts the internal values of the given decimal numbers on the same scale.
     *
     * @return array{string, string} The scaled integer values of $x and $y.
     */
    private function scaleValues(BigDecimal $x, BigDecimal $y) : array
    {
        $a = $x->value;
        $b = $y->value;

        if ($b !== '0' && $x->scale > $y->scale) {
            $b .= \str_repeat('0', $x->scale - $y->scale);
        } elseif ($a !== '0' && $x->scale < $y->scale) {
            $a .= \str_repeat('0', $y->scale - $x->scale);
        }

        return [$a, $b];
    }

    private function valueWithMinScale(int $scale) : string
    {
        $value = $this->value;

        if ($this->value !== '0' && $scale > $this->scale) {
            $value .= \str_repeat('0', $scale - $this->scale);
        }

        return $value;
    }

    /**
     * Adds leading zeros if necessary to the unscaled value to represent the full decimal number.
     */
    private function getUnscaledValueWithLeadingZeros() : string
    {
        $value = $this->value;
        $targetLength = $this->scale + 1;
        $negative = ($value[0] === '-');
        $length = \strlen($value);

        if ($negative) {
            $length--;
        }

        if ($length >= $targetLength) {
            return $this->value;
        }

        if ($negative) {
            $value = \substr($value, 1);
        }

        $value = \str_pad($value, $targetLength, '0', STR_PAD_LEFT);

        if ($negative) {
            $value = '-' . $value;
        }

        return $value;
    }
}
