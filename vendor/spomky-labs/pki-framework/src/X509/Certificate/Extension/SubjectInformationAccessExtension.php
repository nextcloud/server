<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X509\Certificate\Extension\AccessDescription\AccessDescription;
use SpomkyLabs\Pki\X509\Certificate\Extension\AccessDescription\SubjectAccessDescription;
use function count;

/**
 * Implements 'Subject Information Access' extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.2.2
 */
final class SubjectInformationAccessExtension extends Extension implements Countable, IteratorAggregate
{
    /**
     * Access descriptions.
     *
     * @var SubjectAccessDescription[]
     */
    private readonly array $accessDescriptions;

    private function __construct(bool $critical, SubjectAccessDescription ...$accessDescriptions)
    {
        parent::__construct(self::OID_SUBJECT_INFORMATION_ACCESS, $critical);
        $this->accessDescriptions = $accessDescriptions;
    }

    public static function create(bool $critical, SubjectAccessDescription ...$accessDescriptions): self
    {
        return new self($critical, ...$accessDescriptions);
    }

    /**
     * Get the access descriptions.
     *
     * @return SubjectAccessDescription[]
     */
    public function accessDescriptions(): array
    {
        return $this->accessDescriptions;
    }

    /**
     * Get the number of access descriptions.
     *
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->accessDescriptions);
    }

    /**
     * Get iterator for access descriptions.
     *
     * @return ArrayIterator List of SubjectAccessDescription objects
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->accessDescriptions);
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        $access = array_map(
            static fn (UnspecifiedType $el) => SubjectAccessDescription::fromASN1($el->asSequence()),
            UnspecifiedType::fromDER($data)->asSequence()->elements()
        );
        return self::create($critical, ...$access);
    }

    protected function valueASN1(): Element
    {
        $elements = array_map(static fn (AccessDescription $access) => $access->toASN1(), $this->accessDescriptions);
        return Sequence::create(...$elements);
    }
}
