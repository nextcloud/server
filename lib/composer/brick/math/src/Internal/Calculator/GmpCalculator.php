<?php

declare(strict_types=1);

namespace Brick\Math\Internal\Calculator;

use Brick\Math\Internal\Calculator;

/**
 * Calculator implementation built around the GMP library.
 *
 * @internal
 *
 * @psalm-immutable
 */
class GmpCalculator extends Calculator
{
    /**
     * {@inheritdoc}
     */
    public function add(string $a, string $b) : string
    {
        return \gmp_strval(\gmp_add($a, $b));
    }

    /**
     * {@inheritdoc}
     */
    public function sub(string $a, string $b) : string
    {
        return \gmp_strval(\gmp_sub($a, $b));
    }

    /**
     * {@inheritdoc}
     */
    public function mul(string $a, string $b) : string
    {
        return \gmp_strval(\gmp_mul($a, $b));
    }

    /**
     * {@inheritdoc}
     */
    public function divQ(string $a, string $b) : string
    {
        return \gmp_strval(\gmp_div_q($a, $b));
    }

    /**
     * {@inheritdoc}
     */
    public function divR(string $a, string $b) : string
    {
        return \gmp_strval(\gmp_div_r($a, $b));
    }

    /**
     * {@inheritdoc}
     */
    public function divQR(string $a, string $b) : array
    {
        [$q, $r] = \gmp_div_qr($a, $b);

        return [
            \gmp_strval($q),
            \gmp_strval($r)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function pow(string $a, int $e) : string
    {
        return \gmp_strval(\gmp_pow($a, $e));
    }

    /**
     * {@inheritdoc}
     */
    public function modInverse(string $x, string $m) : ?string
    {
        $result = \gmp_invert($x, $m);

        if ($result === false) {
            return null;
        }

        return \gmp_strval($result);
    }

    /**
     * {@inheritdoc}
     */
    public function modPow(string $base, string $exp, string $mod) : string
    {
        return \gmp_strval(\gmp_powm($base, $exp, $mod));
    }

    /**
     * {@inheritdoc}
     */
    public function gcd(string $a, string $b) : string
    {
        return \gmp_strval(\gmp_gcd($a, $b));
    }

    /**
     * {@inheritdoc}
     */
    public function fromBase(string $number, int $base) : string
    {
        return \gmp_strval(\gmp_init($number, $base));
    }

    /**
     * {@inheritdoc}
     */
    public function toBase(string $number, int $base) : string
    {
        return \gmp_strval($number, $base);
    }

    /**
     * {@inheritdoc}
     */
    public function and(string $a, string $b) : string
    {
        return \gmp_strval(\gmp_and($a, $b));
    }

    /**
     * {@inheritdoc}
     */
    public function or(string $a, string $b) : string
    {
        return \gmp_strval(\gmp_or($a, $b));
    }

    /**
     * {@inheritdoc}
     */
    public function xor(string $a, string $b) : string
    {
        return \gmp_strval(\gmp_xor($a, $b));
    }

    /**
     * {@inheritDoc}
     */
    public function sqrt(string $n) : string
    {
        return \gmp_strval(\gmp_sqrt($n));
    }
}
