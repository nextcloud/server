<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\GeneralName;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use RuntimeException;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X501\ASN1\Name;
use UnexpectedValueException;
use function count;

/**
 * Implements *GeneralNames* ASN.1 type.
 *
 * Provides convenience methods to retrieve the first value of commonly used CHOICE types.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.6
 */
final class GeneralNames implements Countable, IteratorAggregate
{
    /**
     * GeneralName objects.
     *
     * @var GeneralName[]
     */
    private readonly array $_names;

    /**
     * @param GeneralName ...$names One or more GeneralName objects
     */
    private function __construct(GeneralName ...$names)
    {
        $this->_names = $names;
    }

    public static function create(GeneralName ...$names): self
    {
        return new self(...$names);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        if (count($seq) === 0) {
            throw new UnexpectedValueException('GeneralNames must have at least one GeneralName.');
        }
        $names = array_map(static fn (UnspecifiedType $el) => GeneralName::fromASN1($el->asTagged()), $seq->elements());
        return self::create(...$names);
    }

    /**
     * Check whether GeneralNames contains a GeneralName of given type.
     *
     * @param int $tag One of `GeneralName::TAG_*` enumerations
     */
    public function has(int $tag): bool
    {
        return $this->findFirst($tag) !== null;
    }

    /**
     * Get first GeneralName of given type.
     *
     * @param int $tag One of `GeneralName::TAG_*` enumerations
     */
    public function firstOf(int $tag): GeneralName
    {
        $name = $this->findFirst($tag);
        if ($name === null) {
            throw new UnexpectedValueException("No GeneralName by tag {$tag}.");
        }
        return $name;
    }

    /**
     * Get all GeneralName objects of given type.
     *
     * @param int $tag One of `GeneralName::TAG_*` enumerations
     *
     * @return GeneralName[]
     */
    public function allOf(int $tag): array
    {
        $names = array_filter($this->_names, fn (GeneralName $name) => $name->tag() === $tag);
        return array_values($names);
    }

    /**
     * Get value of the first 'dNSName' type.
     */
    public function firstDNS(): string
    {
        $gn = $this->firstOf(GeneralName::TAG_DNS_NAME);
        if (! $gn instanceof DNSName) {
            throw new RuntimeException(DNSName::class . ' expected, got ' . $gn::class);
        }
        return $gn->name();
    }

    /**
     * Get value of the first 'directoryName' type.
     */
    public function firstDN(): Name
    {
        $gn = $this->firstOf(GeneralName::TAG_DIRECTORY_NAME);
        if (! $gn instanceof DirectoryName) {
            throw new RuntimeException(DirectoryName::class . ' expected, got ' . $gn::class);
        }
        return $gn->dn();
    }

    /**
     * Get value of the first 'uniformResourceIdentifier' type.
     */
    public function firstURI(): string
    {
        $gn = $this->firstOf(GeneralName::TAG_URI);
        if (! $gn instanceof UniformResourceIdentifier) {
            throw new RuntimeException(UniformResourceIdentifier::class . ' expected, got ' . $gn::class);
        }
        return $gn->uri();
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        if (count($this->_names) === 0) {
            throw new LogicException('GeneralNames must have at least one GeneralName.');
        }
        $elements = array_map(static fn (GeneralName $name) => $name->toASN1(), $this->_names);
        return Sequence::create(...$elements);
    }

    /**
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->_names);
    }

    /**
     * Get iterator for GeneralName objects.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->_names);
    }

    /**
     * Find first GeneralName by given tag.
     */
    private function findFirst(int $tag): ?GeneralName
    {
        foreach ($this->_names as $name) {
            if ($name->tag() === $tag) {
                return $name;
            }
        }
        return null;
    }
}
