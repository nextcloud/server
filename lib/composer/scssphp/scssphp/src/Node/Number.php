<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Node;

use ScssPhp\ScssPhp\Base\Range;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\Exception\RangeException;
use ScssPhp\ScssPhp\Exception\SassScriptException;
use ScssPhp\ScssPhp\Node;
use ScssPhp\ScssPhp\Type;
use ScssPhp\ScssPhp\Util;

/**
 * Dimension + optional units
 *
 * {@internal
 *     This is a work-in-progress.
 *
 *     The \ArrayAccess interface is temporary until the migration is complete.
 * }}
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 *
 * @template-implements \ArrayAccess<int, mixed>
 */
class Number extends Node implements \ArrayAccess
{
    const PRECISION = 10;

    /**
     * @var integer
     * @deprecated use {Number::PRECISION} instead to read the precision. Configuring it is not supported anymore.
     */
    public static $precision = self::PRECISION;

    /**
     * @see http://www.w3.org/TR/2012/WD-css3-values-20120308/
     *
     * @var array
     * @phpstan-var array<string, array<string, float|int>>
     */
    protected static $unitTable = [
        'in' => [
            'in' => 1,
            'pc' => 6,
            'pt' => 72,
            'px' => 96,
            'cm' => 2.54,
            'mm' => 25.4,
            'q'  => 101.6,
        ],
        'turn' => [
            'deg'  => 360,
            'grad' => 400,
            'rad'  => 6.28318530717958647692528676, // 2 * M_PI
            'turn' => 1,
        ],
        's' => [
            's'  => 1,
            'ms' => 1000,
        ],
        'Hz' => [
            'Hz'  => 1,
            'kHz' => 0.001,
        ],
        'dpi' => [
            'dpi'  => 1,
            'dpcm' => 1 / 2.54,
            'dppx' => 1 / 96,
        ],
    ];

    /**
     * @var integer|float
     */
    private $dimension;

    /**
     * @var string[]
     * @phpstan-var list<string>
     */
    private $numeratorUnits;

    /**
     * @var string[]
     * @phpstan-var list<string>
     */
    private $denominatorUnits;

    /**
     * Initialize number
     *
     * @param integer|float   $dimension
     * @param string[]|string $numeratorUnits
     * @param string[]        $denominatorUnits
     *
     * @phpstan-param list<string>|string $numeratorUnits
     * @phpstan-param list<string>        $denominatorUnits
     */
    public function __construct($dimension, $numeratorUnits, array $denominatorUnits = [])
    {
        if (is_string($numeratorUnits)) {
            $numeratorUnits = $numeratorUnits ? [$numeratorUnits] : [];
        } elseif (isset($numeratorUnits['numerator_units'], $numeratorUnits['denominator_units'])) {
            // TODO get rid of this once `$number[2]` is not used anymore
            $denominatorUnits = $numeratorUnits['denominator_units'];
            $numeratorUnits = $numeratorUnits['numerator_units'];
        }

        $this->dimension = $dimension;
        $this->numeratorUnits = $numeratorUnits;
        $this->denominatorUnits = $denominatorUnits;
    }

    /**
     * @return float|int
     */
    public function getDimension()
    {
        return $this->dimension;
    }

    /**
     * @return string[]
     */
    public function getNumeratorUnits()
    {
        return $this->numeratorUnits;
    }

    /**
     * @return string[]
     */
    public function getDenominatorUnits()
    {
        return $this->denominatorUnits;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        if ($offset === -3) {
            return ! \is_null($this->sourceColumn);
        }

        if ($offset === -2) {
            return ! \is_null($this->sourceLine);
        }

        if (
            $offset === -1 ||
            $offset === 0 ||
            $offset === 1 ||
            $offset === 2
        ) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        switch ($offset) {
            case -3:
                return $this->sourceColumn;

            case -2:
                return $this->sourceLine;

            case -1:
                return $this->sourceIndex;

            case 0:
                return Type::T_NUMBER;

            case 1:
                return $this->dimension;

            case 2:
                return array('numerator_units' => $this->numeratorUnits, 'denominator_units' => $this->denominatorUnits);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('Number is immutable');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Number is immutable');
    }

    /**
     * Returns true if the number is unitless
     *
     * @return boolean
     */
    public function unitless()
    {
        return \count($this->numeratorUnits) === 0 && \count($this->denominatorUnits) === 0;
    }

    /**
     * Checks whether the number has exactly this unit
     *
     * @param string $unit
     *
     * @return bool
     */
    public function hasUnit($unit)
    {
        return \count($this->numeratorUnits) === 1 && \count($this->denominatorUnits) === 0 && $this->numeratorUnits[0] === $unit;
    }

    /**
     * Returns unit(s) as the product of numerator units divided by the product of denominator units
     *
     * @return string
     */
    public function unitStr()
    {
        if ($this->unitless()) {
            return '';
        }

        return self::getUnitString($this->numeratorUnits, $this->denominatorUnits);
    }

    /**
     * @param float|int $min
     * @param float|int $max
     * @param string|null $name
     *
     * @return float|int
     * @throws SassScriptException
     */
    public function valueInRange($min, $max, $name = null)
    {
        try {
            return Util::checkRange('', new Range($min, $max), $this);
        } catch (RangeException $e) {
            throw SassScriptException::forArgument(sprintf('Expected %s to be within %s%s and %s%3$s', $this, $min, $this->unitStr(), $max), $name);
        }
    }

    /**
     * @param string|null $varName
     *
     * @return void
     */
    public function assertNoUnits($varName = null)
    {
        if ($this->unitless()) {
            return;
        }

        throw SassScriptException::forArgument(sprintf('Expected %s to have no units.', $this), $varName);
    }

    /**
     * @param string      $unit
     * @param string|null $varName
     *
     * @return void
     */
    public function assertUnit($unit, $varName = null)
    {
        if ($this->hasUnit($unit)) {
            return;
        }

        throw SassScriptException::forArgument(sprintf('Expected %s to have unit "%s".', $this, $unit), $varName);
    }

    /**
     * @param Number $other
     *
     * @return void
     */
    public function assertSameUnitOrUnitless(Number $other)
    {
        if ($other->unitless()) {
            return;
        }

        if ($this->numeratorUnits === $other->numeratorUnits && $this->denominatorUnits === $other->denominatorUnits) {
            return;
        }

        throw new SassScriptException(sprintf(
            'Incompatible units %s and %s.',
            self::getUnitString($this->numeratorUnits, $this->denominatorUnits),
            self::getUnitString($other->numeratorUnits, $other->denominatorUnits)
        ));
    }

    /**
     * Returns a copy of this number, converted to the units represented by $newNumeratorUnits and $newDenominatorUnits.
     *
     * This does not throw an error if this number is unitless and
     * $newNumeratorUnits/$newDenominatorUnits are not empty, or vice versa. Instead,
     * it treats all unitless numbers as convertible to and from all units without
     * changing the value.
     *
     * @param string[] $newNumeratorUnits
     * @param string[] $newDenominatorUnits
     *
     * @return Number
     *
     * @phpstan-param list<string> $newNumeratorUnits
     * @phpstan-param list<string> $newDenominatorUnits
     *
     * @throws SassScriptException if this number's units are not compatible with $newNumeratorUnits and $newDenominatorUnits
     */
    public function coerce(array $newNumeratorUnits, array $newDenominatorUnits)
    {
        return new Number($this->valueInUnits($newNumeratorUnits, $newDenominatorUnits), $newNumeratorUnits, $newDenominatorUnits);
    }

    /**
     * @param Number $other
     *
     * @return bool
     */
    public function isComparableTo(Number $other)
    {
        if ($this->unitless() || $other->unitless()) {
            return true;
        }

        try {
            $this->greaterThan($other);
            return true;
        } catch (SassScriptException $e) {
            return false;
        }
    }

    /**
     * @param Number $other
     *
     * @return bool
     */
    public function lessThan(Number $other)
    {
        return $this->coerceUnits($other, function ($num1, $num2) {
            return $num1 < $num2;
        });
    }

    /**
     * @param Number $other
     *
     * @return bool
     */
    public function lessThanOrEqual(Number $other)
    {
        return $this->coerceUnits($other, function ($num1, $num2) {
            return $num1 <= $num2;
        });
    }

    /**
     * @param Number $other
     *
     * @return bool
     */
    public function greaterThan(Number $other)
    {
        return $this->coerceUnits($other, function ($num1, $num2) {
            return $num1 > $num2;
        });
    }

    /**
     * @param Number $other
     *
     * @return bool
     */
    public function greaterThanOrEqual(Number $other)
    {
        return $this->coerceUnits($other, function ($num1, $num2) {
            return $num1 >= $num2;
        });
    }

    /**
     * @param Number $other
     *
     * @return Number
     */
    public function plus(Number $other)
    {
        return $this->coerceNumber($other, function ($num1, $num2) {
            return $num1 + $num2;
        });
    }

    /**
     * @param Number $other
     *
     * @return Number
     */
    public function minus(Number $other)
    {
        return $this->coerceNumber($other, function ($num1, $num2) {
            return $num1 - $num2;
        });
    }

    /**
     * @return Number
     */
    public function unaryMinus()
    {
        return new Number(-$this->dimension, $this->numeratorUnits, $this->denominatorUnits);
    }

    /**
     * @param Number $other
     *
     * @return Number
     */
    public function modulo(Number $other)
    {
        return $this->coerceNumber($other, function ($num1, $num2) {
            if ($num2 == 0) {
                return NAN;
            }

            $result = fmod($num1, $num2);

            if ($result == 0) {
                return 0;
            }

            if ($num2 < 0 xor $num1 < 0) {
                $result += $num2;
            }

            return $result;
        });
    }

    /**
     * @param Number $other
     *
     * @return Number
     */
    public function times(Number $other)
    {
        return $this->multiplyUnits($this->dimension * $other->dimension, $this->numeratorUnits, $this->denominatorUnits, $other->numeratorUnits, $other->denominatorUnits);
    }

    /**
     * @param Number $other
     *
     * @return Number
     */
    public function dividedBy(Number $other)
    {
        if ($other->dimension == 0) {
            if ($this->dimension == 0) {
                $value = NAN;
            } elseif ($this->dimension > 0) {
                $value = INF;
            } else {
                $value = -INF;
            }
        } else {
            $value = $this->dimension / $other->dimension;
        }

        return $this->multiplyUnits($value, $this->numeratorUnits, $this->denominatorUnits, $other->denominatorUnits, $other->numeratorUnits);
    }

    /**
     * @param Number $other
     *
     * @return bool
     */
    public function equals(Number $other)
    {
        // Unitless numbers are convertable to unit numbers, but not equal, so we special-case unitless here.
        if ($this->unitless() !== $other->unitless()) {
            return false;
        }

        // In Sass, neither NaN nor Infinity are equal to themselves, while PHP defines INF==INF
        if (is_nan($this->dimension) || is_nan($other->dimension) || !is_finite($this->dimension) || !is_finite($other->dimension)) {
            return false;
        }

        if ($this->unitless()) {
            return round($this->dimension, self::PRECISION) == round($other->dimension, self::PRECISION);
        }

        try {
            return $this->coerceUnits($other, function ($num1, $num2) {
                return round($num1,self::PRECISION) == round($num2, self::PRECISION);
            });
        } catch (SassScriptException $e) {
            return false;
        }
    }

    /**
     * Output number
     *
     * @param \ScssPhp\ScssPhp\Compiler $compiler
     *
     * @return string
     */
    public function output(Compiler $compiler = null)
    {
        $dimension = round($this->dimension, self::PRECISION);

        if (is_nan($dimension)) {
            return 'NaN';
        }

        if ($dimension === INF) {
            return 'Infinity';
        }

        if ($dimension === -INF) {
            return '-Infinity';
        }

        if ($compiler) {
            $unit = $this->unitStr();
        } elseif (isset($this->numeratorUnits[0])) {
            $unit = $this->numeratorUnits[0];
        } else {
            $unit = '';
        }

        $dimension = number_format($dimension, self::PRECISION, '.', '');

        return rtrim(rtrim($dimension, '0'), '.') . $unit;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->output();
    }

    /**
     * @param Number   $other
     * @param callable $operation
     *
     * @return Number
     *
     * @phpstan-param callable(int|float, int|float): (int|float) $operation
     */
    private function coerceNumber(Number $other, $operation)
    {
        $result = $this->coerceUnits($other, $operation);

        if (!$this->unitless()) {
            return new Number($result, $this->numeratorUnits, $this->denominatorUnits);
        }

        return new Number($result, $other->numeratorUnits, $other->denominatorUnits);
    }

    /**
     * @param Number $other
     * @param callable $operation
     *
     * @return mixed
     *
     * @phpstan-template T
     * @phpstan-param callable(int|float, int|float): T $operation
     * @phpstan-return T
     */
    private function coerceUnits(Number $other, $operation)
    {
        if (!$this->unitless()) {
            $num1 = $this->dimension;
            $num2 = $other->valueInUnits($this->numeratorUnits, $this->denominatorUnits);
        } else {
            $num1 = $this->valueInUnits($other->numeratorUnits, $other->denominatorUnits);
            $num2 = $other->dimension;
        }

        return \call_user_func($operation, $num1, $num2);
    }

    /**
     * @param string[] $numeratorUnits
     * @param string[] $denominatorUnits
     *
     * @return int|float
     *
     * @phpstan-param list<string> $numeratorUnits
     * @phpstan-param list<string> $denominatorUnits
     *
     * @throws SassScriptException if this number's units are not compatible with $numeratorUnits and $denominatorUnits
     */
    private function valueInUnits(array $numeratorUnits, array $denominatorUnits)
    {
        if (
            $this->unitless()
            || (\count($numeratorUnits) === 0 && \count($denominatorUnits) === 0)
            || ($this->numeratorUnits === $numeratorUnits && $this->denominatorUnits === $denominatorUnits)
        ) {
            return $this->dimension;
        }

        $value = $this->dimension;
        $oldNumerators = $this->numeratorUnits;

        foreach ($numeratorUnits as $newNumerator) {
            foreach ($oldNumerators as $key => $oldNumerator) {
                $conversionFactor = self::getConversionFactor($newNumerator, $oldNumerator);

                if (\is_null($conversionFactor)) {
                    continue;
                }

                $value *= $conversionFactor;
                unset($oldNumerators[$key]);
                continue 2;
            }

            throw new SassScriptException(sprintf(
                'Incompatible units %s and %s.',
                self::getUnitString($this->numeratorUnits, $this->denominatorUnits),
                self::getUnitString($numeratorUnits, $denominatorUnits)
            ));
        }

        $oldDenominators = $this->denominatorUnits;

        foreach ($denominatorUnits as $newDenominator) {
            foreach ($oldDenominators as $key => $oldDenominator) {
                $conversionFactor = self::getConversionFactor($newDenominator, $oldDenominator);

                if (\is_null($conversionFactor)) {
                    continue;
                }

                $value /= $conversionFactor;
                unset($oldDenominators[$key]);
                continue 2;
            }

            throw new SassScriptException(sprintf(
                'Incompatible units %s and %s.',
                self::getUnitString($this->numeratorUnits, $this->denominatorUnits),
                self::getUnitString($numeratorUnits, $denominatorUnits)
            ));
        }

        if (\count($oldNumerators) || \count($oldDenominators)) {
            throw new SassScriptException(sprintf(
                'Incompatible units %s and %s.',
                self::getUnitString($this->numeratorUnits, $this->denominatorUnits),
                self::getUnitString($numeratorUnits, $denominatorUnits)
            ));
        }

        return $value;
    }

    /**
     * @param int|float $value
     * @param string[] $numerators1
     * @param string[] $denominators1
     * @param string[] $numerators2
     * @param string[] $denominators2
     *
     * @return Number
     *
     * @phpstan-param list<string> $numerators1
     * @phpstan-param list<string> $denominators1
     * @phpstan-param list<string> $numerators2
     * @phpstan-param list<string> $denominators2
     */
    private function multiplyUnits($value, array $numerators1, array $denominators1, array $numerators2, array $denominators2)
    {
        $newNumerators = array();

        foreach ($numerators1 as $numerator) {
            foreach ($denominators2 as $key => $denominator) {
                $conversionFactor = self::getConversionFactor($numerator, $denominator);

                if (\is_null($conversionFactor)) {
                    continue;
                }

                $value /= $conversionFactor;
                unset($denominators2[$key]);
                continue 2;
            }

            $newNumerators[] = $numerator;
        }

        foreach ($numerators2 as $numerator) {
            foreach ($denominators1 as $key => $denominator) {
                $conversionFactor = self::getConversionFactor($numerator, $denominator);

                if (\is_null($conversionFactor)) {
                    continue;
                }

                $value /= $conversionFactor;
                unset($denominators1[$key]);
                continue 2;
            }

            $newNumerators[] = $numerator;
        }

        $newDenominators = array_values(array_merge($denominators1, $denominators2));

        return new Number($value, $newNumerators, $newDenominators);
    }

    /**
     * Returns the number of [unit1]s per [unit2].
     *
     * Equivalently, `1unit1 * conversionFactor(unit1, unit2) = 1unit2`.
     *
     * @param string $unit1
     * @param string $unit2
     *
     * @return float|int|null
     */
    private static function getConversionFactor($unit1, $unit2)
    {
        if ($unit1 === $unit2) {
            return 1;
        }

        foreach (static::$unitTable as $unitVariants) {
            if (isset($unitVariants[$unit1]) && isset($unitVariants[$unit2])) {
                return $unitVariants[$unit1] / $unitVariants[$unit2];
            }
        }

        return null;
    }

    /**
     * Returns unit(s) as the product of numerator units divided by the product of denominator units
     *
     * @param string[] $numerators
     * @param string[] $denominators
     *
     * @phpstan-param list<string> $numerators
     * @phpstan-param list<string> $denominators
     *
     * @return string
     */
    private static function getUnitString(array $numerators, array $denominators)
    {
        if (!\count($numerators)) {
            if (\count($denominators) === 0) {
                return 'no units';
            }

            if (\count($denominators) === 1) {
                return $denominators[0] . '^-1';
            }

            return '(' . implode('*', $denominators) . ')^-1';
        }

        return implode('*', $numerators) . (\count($denominators) ? '/' . implode('*', $denominators) : '');
    }
}
