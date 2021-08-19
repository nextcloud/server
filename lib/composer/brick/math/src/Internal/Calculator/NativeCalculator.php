<?php

declare(strict_types=1);

namespace Brick\Math\Internal\Calculator;

use Brick\Math\Internal\Calculator;

/**
 * Calculator implementation using only native PHP code.
 *
 * @internal
 *
 * @psalm-immutable
 */
class NativeCalculator extends Calculator
{
    /**
     * The max number of digits the platform can natively add, subtract, multiply or divide without overflow.
     * For multiplication, this represents the max sum of the lengths of both operands.
     *
     * For addition, it is assumed that an extra digit can hold a carry (1) without overflowing.
     * Example: 32-bit: max number 1,999,999,999 (9 digits + carry)
     *          64-bit: max number 1,999,999,999,999,999,999 (18 digits + carry)
     *
     * @var int
     */
    private $maxDigits;

    /**
     * Class constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        switch (PHP_INT_SIZE) {
            case 4:
                $this->maxDigits = 9;
                break;

            case 8:
                $this->maxDigits = 18;
                break;

            default:
                throw new \RuntimeException('The platform is not 32-bit or 64-bit as expected.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $a, string $b) : string
    {
        /**
         * @psalm-var numeric-string $a
         * @psalm-var numeric-string $b
         */
        $result = $a + $b;

        if (is_int($result)) {
            return (string) $result;
        }

        if ($a === '0') {
            return $b;
        }

        if ($b === '0') {
            return $a;
        }

        [$aNeg, $bNeg, $aDig, $bDig] = $this->init($a, $b);

        $result = $aNeg === $bNeg ? $this->doAdd($aDig, $bDig) : $this->doSub($aDig, $bDig);

        if ($aNeg) {
            $result = $this->neg($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function sub(string $a, string $b) : string
    {
        return $this->add($a, $this->neg($b));
    }

    /**
     * {@inheritdoc}
     */
    public function mul(string $a, string $b) : string
    {
        /**
         * @psalm-var numeric-string $a
         * @psalm-var numeric-string $b
         */
        $result = $a * $b;

        if (is_int($result)) {
            return (string) $result;
        }

        if ($a === '0' || $b === '0') {
            return '0';
        }

        if ($a === '1') {
            return $b;
        }

        if ($b === '1') {
            return $a;
        }

        if ($a === '-1') {
            return $this->neg($b);
        }

        if ($b === '-1') {
            return $this->neg($a);
        }

        [$aNeg, $bNeg, $aDig, $bDig] = $this->init($a, $b);

        $result = $this->doMul($aDig, $bDig);

        if ($aNeg !== $bNeg) {
            $result = $this->neg($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function divQ(string $a, string $b) : string
    {
        return $this->divQR($a, $b)[0];
    }

    /**
     * {@inheritdoc}
     */
    public function divR(string $a, string $b): string
    {
        return $this->divQR($a, $b)[1];
    }

    /**
     * {@inheritdoc}
     */
    public function divQR(string $a, string $b) : array
    {
        if ($a === '0') {
            return ['0', '0'];
        }

        if ($a === $b) {
            return ['1', '0'];
        }

        if ($b === '1') {
            return [$a, '0'];
        }

        if ($b === '-1') {
            return [$this->neg($a), '0'];
        }

        /** @psalm-var numeric-string $a */
        $na = $a * 1; // cast to number

        if (is_int($na)) {
            /** @psalm-var numeric-string $b */
            $nb = $b * 1;

            if (is_int($nb)) {
                // the only division that may overflow is PHP_INT_MIN / -1,
                // which cannot happen here as we've already handled a divisor of -1 above.
                $r = $na % $nb;
                $q = ($na - $r) / $nb;

                assert(is_int($q));

                return [
                    (string) $q,
                    (string) $r
                ];
            }
        }

        [$aNeg, $bNeg, $aDig, $bDig] = $this->init($a, $b);

        [$q, $r] = $this->doDiv($aDig, $bDig);

        if ($aNeg !== $bNeg) {
            $q = $this->neg($q);
        }

        if ($aNeg) {
            $r = $this->neg($r);
        }

        return [$q, $r];
    }

    /**
     * {@inheritdoc}
     */
    public function pow(string $a, int $e) : string
    {
        if ($e === 0) {
            return '1';
        }

        if ($e === 1) {
            return $a;
        }

        $odd = $e % 2;
        $e -= $odd;

        $aa = $this->mul($a, $a);

        /** @psalm-suppress PossiblyInvalidArgument We're sure that $e / 2 is an int now */
        $result = $this->pow($aa, $e / 2);

        if ($odd === 1) {
            $result = $this->mul($result, $a);
        }

        return $result;
    }

    /**
     * Algorithm from: https://www.geeksforgeeks.org/modular-exponentiation-power-in-modular-arithmetic/
     *
     * {@inheritdoc}
     */
    public function modPow(string $base, string $exp, string $mod) : string
    {
        // special case: the algorithm below fails with 0 power 0 mod 1 (returns 1 instead of 0)
        if ($base === '0' && $exp === '0' && $mod === '1') {
            return '0';
        }

        // special case: the algorithm below fails with power 0 mod 1 (returns 1 instead of 0)
        if ($exp === '0' && $mod === '1') {
            return '0';
        }

        $x = $base;

        $res = '1';

        // numbers are positive, so we can use remainder instead of modulo
        $x = $this->divR($x, $mod);

        while ($exp !== '0') {
            if (in_array($exp[-1], ['1', '3', '5', '7', '9'])) { // odd
                $res = $this->divR($this->mul($res, $x), $mod);
            }

            $exp = $this->divQ($exp, '2');
            $x = $this->divR($this->mul($x, $x), $mod);
        }

        return $res;
    }

    /**
     * Adapted from https://cp-algorithms.com/num_methods/roots_newton.html
     *
     * {@inheritDoc}
     */
    public function sqrt(string $n) : string
    {
        if ($n === '0') {
            return '0';
        }

        // initial approximation
        $x = \str_repeat('9', \intdiv(\strlen($n), 2) ?: 1);

        $decreased = false;

        for (;;) {
            $nx = $this->divQ($this->add($x, $this->divQ($n, $x)), '2');

            if ($x === $nx || $this->cmp($nx, $x) > 0 && $decreased) {
                break;
            }

            $decreased = $this->cmp($nx, $x) < 0;
            $x = $nx;
        }

        return $x;
    }

    /**
     * Performs the addition of two non-signed large integers.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     *
     * @return string
     */
    private function doAdd(string $a, string $b) : string
    {
        [$a, $b, $length] = $this->pad($a, $b);

        $carry = 0;
        $result = '';

        for ($i = $length - $this->maxDigits;; $i -= $this->maxDigits) {
            $blockLength = $this->maxDigits;

            if ($i < 0) {
                $blockLength += $i;
                /** @psalm-suppress LoopInvalidation */
                $i = 0;
            }

            /** @psalm-var numeric-string $blockA */
            $blockA = \substr($a, $i, $blockLength);

            /** @psalm-var numeric-string $blockB */
            $blockB = \substr($b, $i, $blockLength);

            $sum = (string) ($blockA + $blockB + $carry);
            $sumLength = \strlen($sum);

            if ($sumLength > $blockLength) {
                $sum = \substr($sum, 1);
                $carry = 1;
            } else {
                if ($sumLength < $blockLength) {
                    $sum = \str_repeat('0', $blockLength - $sumLength) . $sum;
                }
                $carry = 0;
            }

            $result = $sum . $result;

            if ($i === 0) {
                break;
            }
        }

        if ($carry === 1) {
            $result = '1' . $result;
        }

        return $result;
    }

    /**
     * Performs the subtraction of two non-signed large integers.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     *
     * @return string
     */
    private function doSub(string $a, string $b) : string
    {
        if ($a === $b) {
            return '0';
        }

        // Ensure that we always subtract to a positive result: biggest minus smallest.
        $cmp = $this->doCmp($a, $b);

        $invert = ($cmp === -1);

        if ($invert) {
            $c = $a;
            $a = $b;
            $b = $c;
        }

        [$a, $b, $length] = $this->pad($a, $b);

        $carry = 0;
        $result = '';

        $complement = 10 ** $this->maxDigits;

        for ($i = $length - $this->maxDigits;; $i -= $this->maxDigits) {
            $blockLength = $this->maxDigits;

            if ($i < 0) {
                $blockLength += $i;
                /** @psalm-suppress LoopInvalidation */
                $i = 0;
            }

            /** @psalm-var numeric-string $blockA */
            $blockA = \substr($a, $i, $blockLength);

            /** @psalm-var numeric-string $blockB */
            $blockB = \substr($b, $i, $blockLength);

            $sum = $blockA - $blockB - $carry;

            if ($sum < 0) {
                $sum += $complement;
                $carry = 1;
            } else {
                $carry = 0;
            }

            $sum = (string) $sum;
            $sumLength = \strlen($sum);

            if ($sumLength < $blockLength) {
                $sum = \str_repeat('0', $blockLength - $sumLength) . $sum;
            }

            $result = $sum . $result;

            if ($i === 0) {
                break;
            }
        }

        // Carry cannot be 1 when the loop ends, as a > b
        assert($carry === 0);

        $result = \ltrim($result, '0');

        if ($invert) {
            $result = $this->neg($result);
        }

        return $result;
    }

    /**
     * Performs the multiplication of two non-signed large integers.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     *
     * @return string
     */
    private function doMul(string $a, string $b) : string
    {
        $x = \strlen($a);
        $y = \strlen($b);

        $maxDigits = \intdiv($this->maxDigits, 2);
        $complement = 10 ** $maxDigits;

        $result = '0';

        for ($i = $x - $maxDigits;; $i -= $maxDigits) {
            $blockALength = $maxDigits;

            if ($i < 0) {
                $blockALength += $i;
                /** @psalm-suppress LoopInvalidation */
                $i = 0;
            }

            $blockA = (int) \substr($a, $i, $blockALength);

            $line = '';
            $carry = 0;

            for ($j = $y - $maxDigits;; $j -= $maxDigits) {
                $blockBLength = $maxDigits;

                if ($j < 0) {
                    $blockBLength += $j;
                    /** @psalm-suppress LoopInvalidation */
                    $j = 0;
                }

                $blockB = (int) \substr($b, $j, $blockBLength);

                $mul = $blockA * $blockB + $carry;
                $value = $mul % $complement;
                $carry = ($mul - $value) / $complement;

                $value = (string) $value;
                $value = \str_pad($value, $maxDigits, '0', STR_PAD_LEFT);

                $line = $value . $line;

                if ($j === 0) {
                    break;
                }
            }

            if ($carry !== 0) {
                $line = $carry . $line;
            }

            $line = \ltrim($line, '0');

            if ($line !== '') {
                $line .= \str_repeat('0', $x - $blockALength - $i);
                $result = $this->add($result, $line);
            }

            if ($i === 0) {
                break;
            }
        }

        return $result;
    }

    /**
     * Performs the division of two non-signed large integers.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     *
     * @return string[] The quotient and remainder.
     */
    private function doDiv(string $a, string $b) : array
    {
        $cmp = $this->doCmp($a, $b);

        if ($cmp === -1) {
            return ['0', $a];
        }

        $x = \strlen($a);
        $y = \strlen($b);

        // we now know that a >= b && x >= y

        $q = '0'; // quotient
        $r = $a; // remainder
        $z = $y; // focus length, always $y or $y+1

        for (;;) {
            $focus = \substr($a, 0, $z);

            $cmp = $this->doCmp($focus, $b);

            if ($cmp === -1) {
                if ($z === $x) { // remainder < dividend
                    break;
                }

                $z++;
            }

            $zeros = \str_repeat('0', $x - $z);

            $q = $this->add($q, '1' . $zeros);
            $a = $this->sub($a, $b . $zeros);

            $r = $a;

            if ($r === '0') { // remainder == 0
                break;
            }

            $x = \strlen($a);

            if ($x < $y) { // remainder < dividend
                break;
            }

            $z = $y;
        }

        return [$q, $r];
    }

    /**
     * Compares two non-signed large numbers.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     *
     * @return int [-1, 0, 1]
     */
    private function doCmp(string $a, string $b) : int
    {
        $x = \strlen($a);
        $y = \strlen($b);

        $cmp = $x <=> $y;

        if ($cmp !== 0) {
            return $cmp;
        }

        return \strcmp($a, $b) <=> 0; // enforce [-1, 0, 1]
    }

    /**
     * Pads the left of one of the given numbers with zeros if necessary to make both numbers the same length.
     *
     * The numbers must only consist of digits, without leading minus sign.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     *
     * @return array{string, string, int}
     */
    private function pad(string $a, string $b) : array
    {
        $x = \strlen($a);
        $y = \strlen($b);

        if ($x > $y) {
            $b = \str_repeat('0', $x - $y) . $b;

            return [$a, $b, $x];
        }

        if ($x < $y) {
            $a = \str_repeat('0', $y - $x) . $a;

            return [$a, $b, $y];
        }

        return [$a, $b, $x];
    }
}
