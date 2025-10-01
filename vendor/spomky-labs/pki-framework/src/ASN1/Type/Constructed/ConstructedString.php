<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Constructed;

use LogicException;
use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Component\Length;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Exception\DecodeException;
use SpomkyLabs\Pki\ASN1\Type\StringType;
use SpomkyLabs\Pki\ASN1\Type\Structure;
use Stringable;
use function count;

/**
 * Implements constructed type of simple strings.
 *
 * Constructed strings only exist in BER encodings, and often with indefinite length. Generally constructed string must
 * contain only elements that have the same type tag as the constructing element. For example: ``` OCTET STRING (cons) {
 * OCTET STRING (prim) "ABC" OCTET STRING (prim) "DEF" } ``` Canonically this corresponds to a payload of "ABCDEF"
 * string.
 *
 * From API standpoint this can also be seen as a string type (as it implements `StringType`), and thus
 * `UnspecifiedType::asString()` method may return `ConstructedString` instances.
 */
final class ConstructedString extends Structure implements StringType, Stringable
{
    public function __toString(): string
    {
        return $this->string();
    }

    /**
     * Create from a list of string type elements.
     *
     * All strings must have the same type.
     */
    public static function create(StringType ...$elements): self
    {
        if (count($elements) === 0) {
            throw new LogicException('No elements, unable to determine type tag.');
        }
        $tag = $elements[0]->tag();

        return self::createWithTag($tag, ...$elements);
    }

    /**
     * Create from strings with a given type tag.
     *
     * Does not perform any validation on types.
     *
     * @param int $tag Type tag for the constructed string element
     * @param StringType ...$elements Any number of elements
     */
    public static function createWithTag(int $tag, StringType ...$elements): self
    {
        foreach ($elements as $el) {
            if ($el->tag() !== $tag) {
                throw new LogicException('All elements in constructed string must have the same type.');
            }
        }

        return new self($tag, ...$elements);
    }

    /**
     * Get a list of strings in this structure.
     *
     * @return string[]
     */
    public function strings(): array
    {
        return array_map(static fn (Element $el): string => $el->string(), $this->elements);
    }

    /**
     * Get the contained strings concatenated together.
     *
     * NOTE: It's unclear how bit strings with unused bits should be concatenated.
     */
    public function string(): string
    {
        return implode('', $this->strings());
    }

    protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): self
    {
        if (! $identifier->isConstructed()) {
            throw new DecodeException('Structured element must have constructed bit set.');
        }
        $idx = $offset;
        $length = Length::expectFromDER($data, $idx);
        if ($length->isIndefinite()) {
            $type = self::decodeIndefiniteLength($identifier->intTag(), $data, $idx);
        } else {
            $type = self::decodeDefiniteLength($identifier->intTag(), $data, $idx, $length->intLength());
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
    protected static function decodeDefiniteLength(int $typeTag, string $data, int &$offset, int $length): self
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
        return self::createWithTag($typeTag, ...$elements);
    }

    /**
     * Decode elements for an indefinite length.
     *
     * @param string $data DER data
     * @param int $offset Offset to data
     */
    protected static function decodeIndefiniteLength(int $typeTag, string $data, int &$offset): self
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
        $type = self::createWithTag($typeTag, ...$elements);
        $type->indefiniteLength = true;
        return $type;
    }
}
