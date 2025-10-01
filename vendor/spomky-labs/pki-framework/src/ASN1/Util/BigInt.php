<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Util;

use Brick\Math\BigInteger;
use InvalidArgumentException;
use Stringable;
use Throwable;
use function mb_strlen;

/**
 * Class to wrap an integer of arbirtary length.
 * @see \SpomkyLabs\Pki\Test\ASN1\Util\BigIntTest
 */
final class BigInt implements Stringable
{
    /**
     * Number as a BigInteger object.
     */
    private readonly BigInteger $value;

    /**
     * Number as a base 10 integer string.
     *
     * @internal Lazily initialized
     */
    private ?string $number = null;

    /**
     * Number as an integer type.
     *
     * @internal Lazily initialized
     */
    private ?int $_intNum = null;

    private function __construct(BigInteger|int|string $num)
    {
        // convert to BigInteger object
        if (! $num instanceof BigInteger) {
            try {
                $num = BigInteger::of($num);
            } catch (Throwable) {
                throw new InvalidArgumentException('Unable to convert to integer.');
            }
        }
        $this->value = $num;
    }

    public function __toString(): string
    {
        return $this->base10();
    }

    public static function create(BigInteger|int|string $num): self
    {
        return new self($num);
    }

    /**
     * Initialize from an arbitrary length of octets as an unsigned integer.
     */
    public static function fromUnsignedOctets(string $octets): self
    {
        if (mb_strlen($octets, '8bit') === 0) {
            throw new InvalidArgumentException('Empty octets.');
        }
        return self::create(BigInteger::fromBytes($octets, false));
    }

    /**
     * Initialize from an arbitrary length of octets as an signed integer having two's complement encoding.
     */
    public static function fromSignedOctets(string $octets): self
    {
        if (mb_strlen($octets, '8bit') === 0) {
            throw new InvalidArgumentException('Empty octets.');
        }

        return self::create(BigInteger::fromBytes($octets));
    }

    /**
     * Get the number as a base 10 integer string.
     */
    public function base10(): string
    {
        if ($this->number === null) {
            $this->number = $this->value->toBase(10);
        }
        return $this->number;
    }

    /**
     * Get the number as an integer.
     */
    public function toInt(): int
    {
        if (! isset($this->_intNum)) {
            $this->_intNum = $this->value->toInt();
        }
        return $this->_intNum;
    }

    public function getValue(): BigInteger
    {
        return $this->value;
    }

    /**
     * Get the number as an unsigned integer encoded in binary.
     */
    public function unsignedOctets(): string
    {
        return $this->value->toBytes(false);
    }

    /**
     * Get the number as a signed integer encoded in two's complement binary.
     */
    public function signedOctets(): string
    {
        return $this->value->toBytes();
    }
}
