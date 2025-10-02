<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\NameConstraints;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use UnexpectedValueException;
use function count;

/**
 * Implements *GeneralSubtrees* ASN.1 type used by 'Name Constraints' certificate extension.
 *
 * @see @link https://tools.ietf.org/html/rfc5280#section-4.2.1.10
 */
final class GeneralSubtrees implements Countable, IteratorAggregate
{
    /**
     * Subtrees.
     *
     * @var GeneralSubtree[]
     */
    private readonly array $subtrees;

    private function __construct(GeneralSubtree ...$subtrees)
    {
        $this->subtrees = $subtrees;
    }

    public static function create(GeneralSubtree ...$subtrees): self
    {
        return new self(...$subtrees);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $subtrees = array_map(
            static fn (UnspecifiedType $el) => GeneralSubtree::fromASN1($el->asSequence()),
            $seq->elements()
        );
        if (count($subtrees) === 0) {
            throw new UnexpectedValueException('GeneralSubtrees must contain at least one GeneralSubtree.');
        }
        return self::create(...$subtrees);
    }

    /**
     * Get all subtrees.
     *
     * @return GeneralSubtree[]
     */
    public function all(): array
    {
        return $this->subtrees;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        if (count($this->subtrees) === 0) {
            throw new LogicException('No subtrees.');
        }
        $elements = array_map(static fn (GeneralSubtree $gs) => $gs->toASN1(), $this->subtrees);
        return Sequence::create(...$elements);
    }

    /**
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->subtrees);
    }

    /**
     * Get iterator for subtrees.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->subtrees);
    }
}
