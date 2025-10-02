<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Boolean;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectIdentifier;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements 'AA Controls' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-7.4
 */
final class AAControlsExtension extends Extension
{
    /**
     * @param null|string[] $permittedAttrs
     * @param null|string[] $excludedAttrs
     */
    private function __construct(
        bool $critical,
        private readonly ?int $pathLenConstraint,
        private readonly ?array $permittedAttrs,
        private readonly ?array $excludedAttrs,
        private readonly bool $permitUnSpecified
    ) {
        parent::__construct(self::OID_AA_CONTROLS, $critical);
    }

    /**
     * @param bool $critical Path length contraint.
     * @param null|string[] $permittedAttrs Permitted attributes.
     * @param null|string[] $excludedAttrs Excluded attributes.
     * @param bool $permitUnSpecified Whether to permit unspecified attributes.
     */
    public static function create(
        bool $critical,
        ?int $pathLenConstraint = null,
        ?array $permittedAttrs = null,
        ?array $excludedAttrs = null,
        bool $permitUnSpecified = true
    ): self {
        return new self($critical, $pathLenConstraint, $permittedAttrs, $excludedAttrs, $permitUnSpecified);
    }

    /**
     * Check whether path length constraint is present.
     */
    public function hasPathLen(): bool
    {
        return isset($this->pathLenConstraint);
    }

    /**
     * Get path length constraint.
     */
    public function pathLen(): int
    {
        if (! $this->hasPathLen()) {
            throw new LogicException('pathLen not set.');
        }
        return $this->pathLenConstraint;
    }

    /**
     * Check whether permitted attributes are present.
     */
    public function hasPermittedAttrs(): bool
    {
        return isset($this->permittedAttrs);
    }

    /**
     * Get OID's of permitted attributes.
     *
     * @return string[]
     */
    public function permittedAttrs(): array
    {
        if (! $this->hasPermittedAttrs()) {
            throw new LogicException('permittedAttrs not set.');
        }
        return $this->permittedAttrs;
    }

    /**
     * Check whether excluded attributes are present.
     */
    public function hasExcludedAttrs(): bool
    {
        return isset($this->excludedAttrs);
    }

    /**
     * Get OID's of excluded attributes.
     *
     * @return string[]
     */
    public function excludedAttrs(): array
    {
        if (! $this->hasExcludedAttrs()) {
            throw new LogicException('excludedAttrs not set.');
        }
        return $this->excludedAttrs;
    }

    /**
     * Whether to permit attributes that are not explicitly specified in neither permitted nor excluded list.
     */
    public function permitUnspecified(): bool
    {
        return $this->permitUnSpecified;
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        $seq = UnspecifiedType::fromDER($data)->asSequence();
        $path_len = null;
        $permitted = null;
        $excluded = null;
        $permit_unspecified = true;
        $idx = 0;
        if ($seq->has($idx, Element::TYPE_INTEGER)) {
            $path_len = $seq->at($idx++)
                ->asInteger()
                ->intNumber();
        }
        if ($seq->hasTagged(0)) {
            $attr_seq = $seq->getTagged(0)
                ->asImplicit(Element::TYPE_SEQUENCE)
                ->asSequence();
            $permitted = array_map(
                static fn (UnspecifiedType $el) => $el->asObjectIdentifier()
                    ->oid(),
                $attr_seq->elements()
            );
            ++$idx;
        }
        if ($seq->hasTagged(1)) {
            $attr_seq = $seq->getTagged(1)
                ->asImplicit(Element::TYPE_SEQUENCE)
                ->asSequence();
            $excluded = array_map(
                static fn (UnspecifiedType $el) => $el->asObjectIdentifier()
                    ->oid(),
                $attr_seq->elements()
            );
            ++$idx;
        }
        if ($seq->has($idx, Element::TYPE_BOOLEAN)) {
            $permit_unspecified = $seq->at($idx++)
                ->asBoolean()
                ->value();
        }
        return self::create($critical, $path_len, $permitted, $excluded, $permit_unspecified);
    }

    protected function valueASN1(): Element
    {
        $elements = [];
        if (isset($this->pathLenConstraint)) {
            $elements[] = Integer::create($this->pathLenConstraint);
        }
        if (isset($this->permittedAttrs)) {
            $oids = array_map(static fn ($oid) => ObjectIdentifier::create($oid), $this->permittedAttrs);
            $elements[] = ImplicitlyTaggedType::create(0, Sequence::create(...$oids));
        }
        if (isset($this->excludedAttrs)) {
            $oids = array_map(static fn ($oid) => ObjectIdentifier::create($oid), $this->excludedAttrs);
            $elements[] = ImplicitlyTaggedType::create(1, Sequence::create(...$oids));
        }
        if ($this->permitUnSpecified !== true) {
            $elements[] = Boolean::create(false);
        }
        return Sequence::create(...$elements);
    }
}
