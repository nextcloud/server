<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Component;

use Brick\Math\BigInteger;
use SpomkyLabs\Pki\ASN1\Exception\DecodeException;
use SpomkyLabs\Pki\ASN1\Feature\Encodable;
use SpomkyLabs\Pki\ASN1\Util\BigInt;
use function array_key_exists;
use function mb_strlen;
use function ord;

/**
 * Class to represent BER/DER identifier octets.
 * @see \SpomkyLabs\Pki\Test\ASN1\Component\IdentifierTest
 */
final class Identifier implements Encodable
{
    // Type class enumerations
    final public const CLASS_UNIVERSAL = 0b00;

    final public const CLASS_APPLICATION = 0b01;

    final public const CLASS_CONTEXT_SPECIFIC = 0b10;

    final public const CLASS_PRIVATE = 0b11;

    // P/C enumerations
    final public const PRIMITIVE = 0b0;

    final public const CONSTRUCTED = 0b1;

    /**
     * Mapping from type class to human readable name.
     *
     * @internal
     *
     * @var array<int, string>
     */
    private const MAP_CLASS_TO_NAME = [
        self::CLASS_UNIVERSAL => 'UNIVERSAL',
        self::CLASS_APPLICATION => 'APPLICATION',
        self::CLASS_CONTEXT_SPECIFIC => 'CONTEXT SPECIFIC',
        self::CLASS_PRIVATE => 'PRIVATE',
    ];

    /**
     * Type class.
     */
    private int $_class;

    /**
     * Primitive or Constructed.
     */
    private readonly int $_pc;

    /**
     * Content type tag.
     */
    private BigInt $_tag;

    /**
     * @param int $class Type class
     * @param int $pc Primitive / Constructed
     * @param BigInteger|int $tag Type tag number
     */
    private function __construct(int $class, int $pc, BigInteger|int $tag)
    {
        $this->_class = 0b11 & $class;
        $this->_pc = 0b1 & $pc;
        $this->_tag = BigInt::create($tag);
    }

    public static function create(int $class, int $pc, BigInteger|int $tag): self
    {
        return new self($class, $pc, $tag);
    }

    /**
     * Decode identifier component from DER data.
     *
     * @param string $data DER encoded data
     * @param null|int $offset Reference to the variable that contains offset
     * into the data where to start parsing.
     * Variable is updated to the offset next to the
     * parsed identifier. If null, start from offset 0.
     */
    public static function fromDER(string $data, int &$offset = null): self
    {
        $idx = $offset ?? 0;
        $datalen = mb_strlen($data, '8bit');
        if ($idx >= $datalen) {
            throw new DecodeException('Invalid offset.');
        }
        $byte = ord($data[$idx++]);
        // bits 8 and 7 (class)
        // 0 = universal, 1 = application, 2 = context-specific, 3 = private
        $class = (0b11000000 & $byte) >> 6;
        // bit 6 (0 = primitive / 1 = constructed)
        $pc = (0b00100000 & $byte) >> 5;
        // bits 5 to 1 (tag number)
        $tag = (0b00011111 & $byte);
        // long-form identifier
        if ($tag === 0x1f) {
            $tag = self::decodeLongFormTag($data, $idx);
        }
        if (isset($offset)) {
            $offset = $idx;
        }
        return self::create($class, $pc, $tag);
    }

    public function toDER(): string
    {
        $bytes = [];
        $byte = $this->_class << 6 | $this->_pc << 5;
        $tag = $this->_tag->getValue();
        if ($tag->isLessThan(0x1f)) {
            $bytes[] = $byte | $tag->toInt();
        } // long-form identifier
        else {
            $bytes[] = $byte | 0x1f;
            $octets = [];
            for (; $tag->isGreaterThan(0); $tag = $tag->shiftedRight(7)) {
                $octets[] = 0x80 | $tag->and(0x7f)->toInt();
            }
            // last octet has bit 8 set to zero
            $octets[0] &= 0x7f;
            foreach (array_reverse($octets) as $octet) {
                $bytes[] = $octet;
            }
        }
        return pack('C*', ...$bytes);
    }

    /**
     * Get class of the type.
     */
    public function typeClass(): int
    {
        return $this->_class;
    }

    public function pc(): int
    {
        return $this->_pc;
    }

    /**
     * Get the tag number.
     *
     * @return string Base 10 integer string
     */
    public function tag(): string
    {
        return $this->_tag->base10();
    }

    /**
     * Get the tag as an integer.
     */
    public function intTag(): int
    {
        return $this->_tag->toInt();
    }

    /**
     * Check whether type is of an universal class.
     */
    public function isUniversal(): bool
    {
        return $this->_class === self::CLASS_UNIVERSAL;
    }

    /**
     * Check whether type is of an application class.
     */
    public function isApplication(): bool
    {
        return $this->_class === self::CLASS_APPLICATION;
    }

    /**
     * Check whether type is of a context specific class.
     */
    public function isContextSpecific(): bool
    {
        return $this->_class === self::CLASS_CONTEXT_SPECIFIC;
    }

    /**
     * Check whether type is of a private class.
     */
    public function isPrivate(): bool
    {
        return $this->_class === self::CLASS_PRIVATE;
    }

    /**
     * Check whether content is primitive type.
     */
    public function isPrimitive(): bool
    {
        return $this->_pc === self::PRIMITIVE;
    }

    /**
     * Check hether content is constructed type.
     */
    public function isConstructed(): bool
    {
        return $this->_pc === self::CONSTRUCTED;
    }

    /**
     * Get self with given type class.
     *
     * @param int $class One of `CLASS_*` enumerations
     */
    public function withClass(int $class): self
    {
        $obj = clone $this;
        $obj->_class = 0b11 & $class;
        return $obj;
    }

    /**
     * Get self with given type tag.
     *
     * @param int $tag Tag number
     */
    public function withTag(int $tag): self
    {
        $obj = clone $this;
        $obj->_tag = BigInt::create($tag);
        return $obj;
    }

    /**
     * Get human readable name of the type class.
     */
    public static function classToName(int $class): string
    {
        if (! array_key_exists($class, self::MAP_CLASS_TO_NAME)) {
            return "CLASS {$class}";
        }
        return self::MAP_CLASS_TO_NAME[$class];
    }

    /**
     * Parse long form tag.
     *
     * @param string $data DER data
     * @param int $offset Reference to the variable containing offset to data
     *
     * @return BigInteger Tag number
     */
    private static function decodeLongFormTag(string $data, int &$offset): BigInteger
    {
        $datalen = mb_strlen($data, '8bit');
        $tag = BigInteger::of(0);
        while (true) {
            if ($offset >= $datalen) {
                throw new DecodeException('Unexpected end of data while decoding long form identifier.');
            }
            $byte = ord($data[$offset++]);
            $tag = $tag->shiftedLeft(7);
            $tag = $tag->or(0x7f & $byte);
            // last byte has bit 8 set to zero
            if ((0x80 & $byte) === 0) {
                break;
            }
        }
        return $tag;
    }
}
