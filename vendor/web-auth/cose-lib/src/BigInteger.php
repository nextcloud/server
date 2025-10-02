<?php

declare(strict_types=1);

namespace Cose;

use Brick\Math\BigInteger as BrickBigInteger;
use function chr;
use function hex2bin;
use function unpack;

/**
 * @internal
 */
final class BigInteger
{
    private function __construct(
        private readonly BrickBigInteger $value
    ) {
    }

    public static function createFromBinaryString(string $value): self
    {
        $res = unpack('H*', $value);
        $data = current($res);

        return new self(BrickBigInteger::fromBase($data, 16));
    }

    public static function createFromDecimal(int $value): self
    {
        return new self(BrickBigInteger::of($value));
    }

    /**
     * Converts a BigInteger to a binary string.
     */
    public function toBytes(): string
    {
        if ($this->value->isEqualTo(BrickBigInteger::zero())) {
            return '';
        }

        $temp = $this->value->toBase(16);
        $temp = 0 !== (mb_strlen($temp, '8bit') & 1) ? '0' . $temp : $temp;
        $temp = hex2bin($temp);

        return ltrim($temp, chr(0));
    }

    /**
     * Adds two BigIntegers.
     */
    public function add(self $y): self
    {
        $value = $this->value->plus($y->value);

        return new self($value);
    }

    /**
     * Subtracts two BigIntegers.
     */
    public function subtract(self $y): self
    {
        $value = $this->value->minus($y->value);

        return new self($value);
    }

    /**
     * Multiplies two BigIntegers.
     */
    public function multiply(self $x): self
    {
        $value = $this->value->multipliedBy($x->value);

        return new self($value);
    }

    /**
     * Performs modular exponentiation.
     */
    public function modPow(self $e, self $n): self
    {
        $value = $this->value->modPow($e->value, $n->value);

        return new self($value);
    }

    /**
     * Performs modular exponentiation.
     */
    public function mod(self $d): self
    {
        $value = $this->value->mod($d->value);

        return new self($value);
    }

    /**
     * Compares two numbers.
     */
    public function compare(self $y): int
    {
        return $this->value->compareTo($y->value);
    }
}
