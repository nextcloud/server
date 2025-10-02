<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\ASN1;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use RangeException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X501\ASN1\AttributeValue\AttributeValue;
use SpomkyLabs\Pki\X501\DN\DNParser;
use Stringable;
use function count;

/**
 * Implements *Name* ASN.1 type.
 *
 * Since *Name* is a CHOICE only supporting *RDNSequence* type, this class implements *RDNSequence* semantics as well.
 *
 * @see https://www.itu.int/ITU-T/formal-language/itu-t/x/x501/2012/InformationFramework.html#InformationFramework.Name
 */
final class Name implements Countable, IteratorAggregate, Stringable
{
    /**
     * Relative distinguished name components.
     *
     * @var RDN[]
     */
    private readonly array $rdns;

    /**
     * @param RDN ...$rdns RDN components
     */
    private function __construct(RDN ...$rdns)
    {
        $this->rdns = $rdns;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function create(RDN ...$rdns): self
    {
        return new self(...$rdns);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $rdns = array_map(static fn (UnspecifiedType $el) => RDN::fromASN1($el->asSet()), $seq->elements());
        return self::create(...$rdns);
    }

    /**
     * Initialize from distinguished name string.
     *
     * @see https://tools.ietf.org/html/rfc1779
     */
    public static function fromString(string $str): self
    {
        $rdns = [];
        foreach (DNParser::parseString($str) as $nameComponent) {
            $attribs = [];
            foreach ($nameComponent as [$name, $val]) {
                $type = AttributeType::fromName($name);
                // hexstrings are parsed to ASN.1 elements
                if ($val instanceof Element) {
                    $el = $val;
                } else {
                    $el = AttributeType::asn1StringForType($type->oid(), $val);
                }
                $value = AttributeValue::fromASN1ByOID($type->oid(), $el->asUnspecified());
                $attribs[] = AttributeTypeAndValue::create($type, $value);
            }
            $rdns[] = RDN::create(...$attribs);
        }
        return self::create(...$rdns);
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        $elements = array_map(static fn (RDN $rdn) => $rdn->toASN1(), $this->rdns);
        return Sequence::create(...$elements);
    }

    /**
     * Get distinguised name string conforming to RFC 2253.
     *
     * @see https://tools.ietf.org/html/rfc2253#section-2.1
     */
    public function toString(): string
    {
        $parts = array_map(static fn (RDN $rdn) => $rdn->toString(), array_reverse($this->rdns));
        return implode(',', $parts);
    }

    /**
     * Whether name is semantically equal to other.
     *
     * Comparison conforms to RFC 4518 string preparation algorithm.
     *
     * @see https://tools.ietf.org/html/rfc4518
     *
     * @param Name $other Object to compare to
     */
    public function equals(self $other): bool
    {
        // if RDN count doesn't match
        if (count($this) !== count($other)) {
            return false;
        }
        for ($i = count($this) - 1; $i >= 0; --$i) {
            $rdn1 = $this->rdns[$i];
            $rdn2 = $other->rdns[$i];
            if (! $rdn1->equals($rdn2)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all RDN objects.
     *
     * @return RDN[]
     */
    public function all(): array
    {
        return $this->rdns;
    }

    /**
     * Get the first AttributeValue of given type.
     *
     * Relative name components shall be traversed in encoding order, which is reversed in regards to the string
     * representation. Multi-valued RDN with multiple attributes of the requested type is ambiguous and shall throw an
     * exception.
     *
     * @param string $name Attribute OID or name
     */
    public function firstValueOf(string $name): AttributeValue
    {
        $oid = AttributeType::attrNameToOID($name);
        foreach ($this->rdns as $rdn) {
            $tvs = $rdn->allOf($oid);
            if (count($tvs) > 1) {
                throw new RangeException("RDN with multiple {$name} attributes.");
            }
            if (count($tvs) === 1) {
                return $tvs[0]->value();
            }
        }
        throw new RangeException("Attribute {$name} not found.");
    }

    /**
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->rdns);
    }

    /**
     * Get the number of attributes of given type.
     *
     * @param string $name Attribute OID or name
     */
    public function countOfType(string $name): int
    {
        $oid = AttributeType::attrNameToOID($name);
        return array_sum(array_map(static fn (RDN $rdn): int => count($rdn->allOf($oid)), $this->rdns));
    }

    /**
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->rdns);
    }
}
