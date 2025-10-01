<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Util;

use Brick\Math\BigInteger;
use OutOfBoundsException;
use RuntimeException;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use function assert;
use function count;
use function is_array;
use function ord;

/**
 * Class to handle a bit string as a field of flags.
 * @see \SpomkyLabs\Pki\Test\ASN1\Util\FlagsTest
 */
final class Flags
{
    /**
     * Flag octets.
     */
    private string $_flags;

    /**
     * @param BigInteger|int|string $flags Flags
     * @param int $_width The number of flags. If width is larger than
     * number of bits in $flags, zeroes are prepended
     * to flag field.
     */
    private function __construct(
        BigInteger|int|string $flags,
        private readonly int $_width
    ) {
        if ($_width === 0) {
            $this->_flags = '';
            return;
        }

        // calculate number of unused bits in last octet
        $last_octet_bits = $_width % 8;
        $unused_bits = $last_octet_bits !== 0 ? 8 - $last_octet_bits : 0;
        // mask bits outside bitfield width
        $num = BigInteger::of($flags);
        $mask = BigInteger::of(1)->shiftedLeft($_width)->minus(1);
        $num = $num->and($mask);

        // shift towards MSB if needed
        $data = $num->shiftedLeft($unused_bits)
            ->toBytes(false);
        $octets = unpack('C*', $data);
        assert(is_array($octets), new RuntimeException('unpack() failed'));
        $bits = count($octets) * 8;
        // pad with zeroes
        while ($bits < $_width) {
            array_unshift($octets, 0);
            $bits += 8;
        }
        $this->_flags = pack('C*', ...$octets);
    }

    public static function create(BigInteger|int|string $flags, int $_width): self
    {
        return new self($flags, $_width);
    }

    /**
     * Initialize from `BitString`.
     */
    public static function fromBitString(BitString $bs, int $width): self
    {
        $num_bits = $bs->numBits();
        $data = $bs->string();
        $num = $data === '' ? BigInteger::of(0) : BigInteger::fromBytes($bs->string(), false);
        $num = $num->shiftedRight($bs->unusedBits());
        if ($num_bits < $width) {
            $num = $num->shiftedLeft($width - $num_bits);
        }
        return self::create($num, $width);
    }

    /**
     * Check whether a bit at given index is set.
     *
     * Index 0 is the leftmost bit.
     */
    public function test(int $idx): bool
    {
        if ($idx >= $this->_width) {
            throw new OutOfBoundsException('Index is out of bounds.');
        }
        // octet index
        $oi = (int) floor($idx / 8);
        $byte = $this->_flags[$oi];
        // bit index
        $bi = $idx % 8;
        // index 0 is the most significant bit in byte
        $mask = 0x01 << (7 - $bi);
        return (ord($byte) & $mask) > 0;
    }

    /**
     * Get flags as an octet string.
     *
     * Zeroes are appended to the last octet if width is not divisible by 8.
     */
    public function string(): string
    {
        return $this->_flags;
    }

    /**
     * Get flags as a base 10 integer.
     *
     * @return string Integer as a string
     */
    public function number(): string
    {
        $num = BigInteger::fromBytes($this->_flags, false);
        $last_octet_bits = $this->_width % 8;
        $unused_bits = $last_octet_bits !== 0 ? 8 - $last_octet_bits : 0;
        $num = $num->shiftedRight($unused_bits);
        return $num->toBase(10);
    }

    /**
     * Get flags as an integer.
     */
    public function intNumber(): int
    {
        $num = BigInt::create($this->number());
        return $num->toInt();
    }

    /**
     * Get flags as a `BitString` object.
     *
     * Unused bits are set accordingly. Trailing zeroes are not stripped.
     */
    public function bitString(): BitString
    {
        $last_octet_bits = $this->_width % 8;
        $unused_bits = $last_octet_bits !== 0 ? 8 - $last_octet_bits : 0;
        return BitString::create($this->_flags, $unused_bits);
    }
}
