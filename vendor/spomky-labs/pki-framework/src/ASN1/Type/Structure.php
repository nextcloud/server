<?php

/** @noinspection ALL */

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use OutOfBoundsException;
use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Component\Length;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Exception\DecodeException;
use SpomkyLabs\Pki\ASN1\Feature\ElementBase;
use function count;

/**
 * Base class for the constructed types.
 */
abstract class Structure extends Element implements Countable, IteratorAggregate
{
    use UniversalClass;

    /**
     * Array of elements in the structure.
     *
     * @var Element[]
     */
    protected array $elements;

    /**
     * Lookup table for the tagged elements.
     *
     * @var null|Element[]
     */
    private ?array $taggedMap = null;

    /**
     * Cache variable of elements wrapped into `UnspecifiedType` objects.
     *
     * @var null|UnspecifiedType[]
     */
    private ?array $unspecifiedTypes = null;

    /**
     * @param ElementBase ...$elements Any number of elements
     */
    protected function __construct(int $typeTag, ElementBase ...$elements)
    {
        parent::__construct($typeTag);
        $this->elements = array_map(static fn (ElementBase $el) => $el->asElement(), $elements);
    }

    /**
     * Clone magic method.
     */
    public function __clone()
    {
        // clear cache-variables
        $this->taggedMap = null;
        $this->unspecifiedTypes = null;
    }

    public function isConstructed(): bool
    {
        return true;
    }

    /**
     * Explode DER structure to DER encoded components that it contains.
     *
     * @return string[]
     */
    public static function explodeDER(string $data): array
    {
        $offset = 0;
        $identifier = Identifier::fromDER($data, $offset);
        if (! $identifier->isConstructed()) {
            throw new DecodeException('Element is not constructed.');
        }
        $length = Length::expectFromDER($data, $offset);
        if ($length->isIndefinite()) {
            throw new DecodeException('Explode not implemented for indefinite length encoding.');
        }
        $end = $offset + $length->intLength();
        $parts = [];
        while ($offset < $end) {
            // start of the element
            $idx = $offset;
            // skip identifier
            Identifier::fromDER($data, $offset);
            // decode element length
            $length = Length::expectFromDER($data, $offset)->intLength();
            // extract der encoding of the element
            $parts[] = mb_substr($data, $idx, $offset - $idx + $length, '8bit');
            // update offset over content
            $offset += $length;
        }
        return $parts;
    }

    /**
     * Get self with an element at the given index replaced by another.
     *
     * @param int $idx Element index
     * @param Element $el New element to insert into the structure
     */
    public function withReplaced(int $idx, Element $el): self
    {
        if (! isset($this->elements[$idx])) {
            throw new OutOfBoundsException("Structure doesn't have element at index {$idx}.");
        }
        $obj = clone $this;
        $obj->elements[$idx] = $el;
        return $obj;
    }

    /**
     * Get self with an element inserted before the given index.
     *
     * @param int $idx Element index
     * @param Element $el New element to insert into the structure
     */
    public function withInserted(int $idx, Element $el): self
    {
        if (count($this->elements) < $idx || $idx < 0) {
            throw new OutOfBoundsException("Index {$idx} is out of bounds.");
        }
        $obj = clone $this;
        array_splice($obj->elements, $idx, 0, [$el]);
        return $obj;
    }

    /**
     * Get self with an element appended to the end.
     *
     * @param Element $el Element to insert into the structure
     */
    public function withAppended(Element $el): self
    {
        $obj = clone $this;
        array_push($obj->elements, $el);
        return $obj;
    }

    /**
     * Get self with an element prepended in the beginning.
     *
     * @param Element $el Element to insert into the structure
     */
    public function withPrepended(Element $el): self
    {
        $obj = clone $this;
        array_unshift($obj->elements, $el);
        return $obj;
    }

    /**
     * Get self with an element at the given index removed.
     *
     * @param int $idx Element index
     */
    public function withoutElement(int $idx): self
    {
        if (! isset($this->elements[$idx])) {
            throw new OutOfBoundsException("Structure doesn't have element at index {$idx}.");
        }
        $obj = clone $this;
        array_splice($obj->elements, $idx, 1);
        return $obj;
    }

    /**
     * Get elements in the structure.
     *
     * @return UnspecifiedType[]
     */
    public function elements(): array
    {
        if (! isset($this->unspecifiedTypes)) {
            $this->unspecifiedTypes = array_map(
                static fn (Element $el) => UnspecifiedType::create($el),
                $this->elements
            );
        }
        return $this->unspecifiedTypes;
    }

    /**
     * Check whether the structure has an element at the given index, optionally satisfying given tag expectation.
     *
     * @param int $idx Index 0..n
     * @param null|int $expectedTag Optional type tag expectation
     */
    public function has(int $idx, ?int $expectedTag = null): bool
    {
        if (! isset($this->elements[$idx])) {
            return false;
        }
        if (isset($expectedTag)) {
            if (! $this->elements[$idx]->isType($expectedTag)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the element at the given index, optionally checking that the element has a given tag.
     *
     * @param int $idx Index 0..n
     */
    public function at(int $idx): UnspecifiedType
    {
        if (! isset($this->elements[$idx])) {
            throw new OutOfBoundsException("Structure doesn't have an element at index {$idx}.");
        }
        return UnspecifiedType::create($this->elements[$idx]);
    }

    /**
     * Check whether the structure contains a context specific element with a given tag.
     *
     * @param int $tag Tag number
     */
    public function hasTagged(int $tag): bool
    {
        // lazily build lookup map
        if (! isset($this->taggedMap)) {
            $this->taggedMap = [];
            foreach ($this->elements as $element) {
                if ($element->isTagged()) {
                    $this->taggedMap[$element->tag()] = $element;
                }
            }
        }
        return isset($this->taggedMap[$tag]);
    }

    /**
     * Get a context specific element tagged with a given tag.
     */
    public function getTagged(int $tag): TaggedType
    {
        if (! $this->hasTagged($tag)) {
            throw new LogicException("No tagged element for tag {$tag}.");
        }
        return $this->taggedMap[$tag];
    }

    /**
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->elements);
    }

    /**
     * Get an iterator for the `UnspecifiedElement` objects.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->elements());
    }

    protected function encodedAsDER(): string
    {
        $data = '';
        foreach ($this->elements as $element) {
            $data .= $element->toDER();
        }
        return $data;
    }
}
