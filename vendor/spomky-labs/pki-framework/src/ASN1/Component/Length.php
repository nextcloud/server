<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Component;

use Brick\Math\BigInteger;
use DomainException;
use LogicException;
use SpomkyLabs\Pki\ASN1\Exception\DecodeException;
use SpomkyLabs\Pki\ASN1\Feature\Encodable;
use SpomkyLabs\Pki\ASN1\Util\BigInt;
use function count;
use function mb_strlen;
use function ord;

/**
 * Class to represent BER/DER length octets.
 */
final class Length implements Encodable
{
    /**
     * Length.
     */
    private readonly BigInt $_length;

    /**
     * @param BigInteger|int $length Length
     * @param bool $_indefinite Whether length is indefinite
     */
    private function __construct(
        BigInteger|int $length,
        private readonly bool $_indefinite = false
    ) {
        $this->_length = BigInt::create($length);
    }

    public static function create(BigInteger|int $length, bool $_indefinite = false): self
    {
        return new self($length, $_indefinite);
    }

    /**
     * Decode length component from DER data.
     *
     * @param string $data DER encoded data
     * @param null|int $offset Reference to the variable that contains offset
     * into the data where to start parsing.
     * Variable is updated to the offset next to the
     * parsed length component. If null, start from offset 0.
     */
    public static function fromDER(string $data, int &$offset = null): self
    {
        $idx = $offset ?? 0;
        $datalen = mb_strlen($data, '8bit');
        if ($idx >= $datalen) {
            throw new DecodeException('Unexpected end of data while decoding length.');
        }
        $indefinite = false;
        $byte = ord($data[$idx++]);
        // bits 7 to 1
        $length = (0x7f & $byte);
        // long form
        if ((0x80 & $byte) !== 0) {
            if ($length === 0) {
                $indefinite = true;
            } else {
                if ($idx + $length > $datalen) {
                    throw new DecodeException('Unexpected end of data while decoding long form length.');
                }
                $length = self::decodeLongFormLength($length, $data, $idx);
            }
        }
        if (isset($offset)) {
            $offset = $idx;
        }
        return self::create($length, $indefinite);
    }

    /**
     * Decode length from DER.
     *
     * Throws an exception if length doesn't match with expected or if data doesn't contain enough bytes.
     *
     * Requirement of definite length is relaxed contrary to the specification (sect. 10.1).
     *
     * @param string $data DER data
     * @param int $offset Reference to the offset variable
     * @param null|int $expected Expected length, null to bypass checking
     * @see self::fromDER
     */
    public static function expectFromDER(string $data, int &$offset, int $expected = null): self
    {
        $idx = $offset;
        $length = self::fromDER($data, $idx);
        // if certain length was expected
        if (isset($expected)) {
            if ($length->isIndefinite()) {
                throw new DecodeException(sprintf('Expected length %d, got indefinite.', $expected));
            }
            if ($expected !== $length->intLength()) {
                throw new DecodeException(sprintf('Expected length %d, got %d.', $expected, $length->intLength()));
            }
        }
        // check that enough data is available
        if (! $length->isIndefinite()
            && mb_strlen($data, '8bit') < $idx + $length->intLength()) {
            throw new DecodeException(
                sprintf(
                    'Length %d overflows data, %d bytes left.',
                    $length->intLength(),
                    mb_strlen($data, '8bit') - $idx
                )
            );
        }
        $offset = $idx;
        return $length;
    }

    public function toDER(): string
    {
        $bytes = [];
        if ($this->_indefinite) {
            $bytes[] = 0x80;
        } else {
            $num = $this->_length->getValue();
            // long form
            if ($num->isGreaterThan(127)) {
                $octets = [];
                for (; $num->isGreaterThan(0); $num = $num->shiftedRight(8)) {
                    $octets[] = BigInteger::of(0xff)->and($num)->toInt();
                }
                $count = count($octets);
                // first octet must not be 0xff
                if ($count >= 127) {
                    throw new DomainException('Too many length octets.');
                }
                $bytes[] = 0x80 | $count;
                foreach (array_reverse($octets) as $octet) {
                    $bytes[] = $octet;
                }
            } // short form
            else {
                $bytes[] = $num->toInt();
            }
        }
        return pack('C*', ...$bytes);
    }

    /**
     * Get the length.
     *
     * @return string Length as an integer string
     */
    public function length(): string
    {
        if ($this->_indefinite) {
            throw new LogicException('Length is indefinite.');
        }
        return $this->_length->base10();
    }

    /**
     * Get the length as an integer.
     */
    public function intLength(): int
    {
        if ($this->_indefinite) {
            throw new LogicException('Length is indefinite.');
        }
        return $this->_length->toInt();
    }

    /**
     * Whether length is indefinite.
     */
    public function isIndefinite(): bool
    {
        return $this->_indefinite;
    }

    /**
     * Decode long form length.
     *
     * @param int $length Number of octets
     * @param string $data Data
     * @param int $offset reference to the variable containing offset to the data
     */
    private static function decodeLongFormLength(int $length, string $data, int &$offset): BigInteger
    {
        // first octet must not be 0xff (spec 8.1.3.5c)
        if ($length === 127) {
            throw new DecodeException('Invalid number of length octets.');
        }
        $num = BigInteger::of(0);
        while (--$length >= 0) {
            $byte = ord($data[$offset++]);
            $num = $num->shiftedLeft(8)
                ->or($byte);
        }

        return $num;
    }
}
