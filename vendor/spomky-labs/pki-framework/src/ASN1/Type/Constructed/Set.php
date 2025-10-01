<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Constructed;

use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Component\Length;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Exception\DecodeException;
use SpomkyLabs\Pki\ASN1\Feature\ElementBase;
use SpomkyLabs\Pki\ASN1\Type\Structure;

/**
 * Implements *SET* and *SET OF* types.
 */
final class Set extends Structure
{
    /**
     * @param Element ...$elements Any number of elements
     */
    public static function create(Element ...$elements): self
    {
        return new self(self::TYPE_SET, ...$elements);
    }

    /**
     * Sort by canonical ascending order.
     *
     * Used for DER encoding of *SET* type.
     */
    public function sortedSet(): self
    {
        $obj = clone $this;
        usort(
            $obj->elements,
            function (Element $a, Element $b) {
                if ($a->typeClass() !== $b->typeClass()) {
                    return $a->typeClass() < $b->typeClass() ? -1 : 1;
                }
                return $a->tag() <=> $b->tag();
            }
        );
        return $obj;
    }

    /**
     * Sort by encoding ascending order.
     *
     * Used for DER encoding of *SET OF* type.
     */
    public function sortedSetOf(): self
    {
        $obj = clone $this;
        usort(
            $obj->elements,
            function (Element $a, Element $b) {
                $a_der = $a->toDER();
                $b_der = $b->toDER();
                return strcmp($a_der, $b_der);
            }
        );
        return $obj;
    }

    /**
     * @return self
     */
    protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): ElementBase
    {
        if (! $identifier->isConstructed()) {
            throw new DecodeException('Structured element must have constructed bit set.');
        }
        $idx = $offset;
        $length = Length::expectFromDER($data, $idx);
        if ($length->isIndefinite()) {
            $type = self::decodeIndefiniteLength($data, $idx);
        } else {
            $type = self::decodeDefiniteLength($data, $idx, $length->intLength());
        }
        $offset = $idx;
        return $type;
    }

    /**
     * Decode elements for a definite length.
     *
     * @param string $data DER data
     * @param int $offset Offset to data
     * @param int $length Number of bytes to decode
     */
    protected static function decodeDefiniteLength(string $data, int &$offset, int $length): ElementBase
    {
        $idx = $offset;
        $end = $idx + $length;
        $elements = [];
        while ($idx < $end) {
            $elements[] = Element::fromDER($data, $idx);
            // check that element didn't overflow length
            if ($idx > $end) {
                throw new DecodeException("Structure's content overflows length.");
            }
        }
        $offset = $idx;
        // return instance by static late binding
        return self::create(...$elements);
    }

    /**
     * Decode elements for an indefinite length.
     *
     * @param string $data DER data
     * @param int $offset Offset to data
     */
    protected static function decodeIndefiniteLength(string $data, int &$offset): ElementBase
    {
        $idx = $offset;
        $elements = [];
        $end = mb_strlen($data, '8bit');
        while (true) {
            if ($idx >= $end) {
                throw new DecodeException('Unexpected end of data while decoding indefinite length structure.');
            }
            $el = Element::fromDER($data, $idx);
            if ($el->isType(self::TYPE_EOC)) {
                break;
            }
            $elements[] = $el;
        }
        $offset = $idx;
        $type = self::create(...$elements);
        $type->indefiniteLength = true;
        return $type;
    }
}
