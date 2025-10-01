<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X509\Certificate\Extension\Target\Target;
use SpomkyLabs\Pki\X509\Certificate\Extension\Target\Targets;
use function count;

/**
 * Implements 'AC Targeting' certificate extension.
 *
 * NOTE**: Syntax is *SEQUENCE OF Targets*, but only one *Targets* element must be used. Multiple *Targets* elements
 * shall be merged into single *Targets*.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.3.2
 */
final class TargetInformationExtension extends Extension implements Countable, IteratorAggregate
{
    /**
     * Targets elements.
     *
     * @var Targets[]
     */
    protected array $targets;

    /**
     * Targets[] merged to single Targets.
     */
    private ?Targets $merged = null;

    private function __construct(bool $critical, Targets ...$targets)
    {
        parent::__construct(self::OID_TARGET_INFORMATION, $critical);
        $this->targets = $targets;
    }

    /**
     * Reset internal state on clone.
     */
    public function __clone()
    {
        $this->merged = null;
    }

    public static function create(bool $critical, Targets ...$targets): self
    {
        return new self($critical, ...$targets);
    }

    /**
     * Initialize from one or more Target objects.
     *
     * Extension criticality shall be set to true as specified by RFC 5755.
     */
    public static function fromTargets(Target ...$target): self
    {
        return self::create(true, Targets::create(...$target));
    }

    /**
     * Get all targets.
     */
    public function targets(): Targets
    {
        if ($this->merged === null) {
            $a = [];
            foreach ($this->targets as $targets) {
                $a = array_merge($a, $targets->all());
            }
            $this->merged = Targets::create(...$a);
        }
        return $this->merged;
    }

    /**
     * Get all name targets.
     *
     * @return Target[]
     */
    public function names(): array
    {
        return $this->targets()
            ->nameTargets();
    }

    /**
     * Get all group targets.
     *
     * @return Target[]
     */
    public function groups(): array
    {
        return $this->targets()
            ->groupTargets();
    }

    /**
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->targets());
    }

    /**
     * Get iterator for targets.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->targets()->all());
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        $targets = array_map(
            static fn (UnspecifiedType $el) => Targets::fromASN1($el->asSequence()),
            UnspecifiedType::fromDER($data)->asSequence()->elements()
        );
        return self::create($critical, ...$targets);
    }

    protected function valueASN1(): Element
    {
        $elements = array_map(static fn (Targets $targets) => $targets->toASN1(), $this->targets);
        return Sequence::create(...$elements);
    }
}
