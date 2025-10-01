<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X509\Certificate\Extension\NameConstraints\GeneralSubtrees;

/**
 * Implements 'Name Constraints' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.10
 */
final class NameConstraintsExtension extends Extension
{
    private function __construct(
        bool $critical,
        private readonly ?GeneralSubtrees $permitted,
        private readonly ?GeneralSubtrees $excluded
    ) {
        parent::__construct(self::OID_NAME_CONSTRAINTS, $critical);
    }

    public static function create(
        bool $critical,
        ?GeneralSubtrees $permitted = null,
        ?GeneralSubtrees $excluded = null
    ): self {
        return new self($critical, $permitted, $excluded);
    }

    /**
     * Whether permitted subtrees are present.
     */
    public function hasPermittedSubtrees(): bool
    {
        return isset($this->permitted);
    }

    /**
     * Get permitted subtrees.
     */
    public function permittedSubtrees(): GeneralSubtrees
    {
        if (! $this->hasPermittedSubtrees()) {
            throw new LogicException('No permitted subtrees.');
        }
        return $this->permitted;
    }

    /**
     * Whether excluded subtrees are present.
     */
    public function hasExcludedSubtrees(): bool
    {
        return isset($this->excluded);
    }

    /**
     * Get excluded subtrees.
     */
    public function excludedSubtrees(): GeneralSubtrees
    {
        if (! $this->hasExcludedSubtrees()) {
            throw new LogicException('No excluded subtrees.');
        }
        return $this->excluded;
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        $seq = UnspecifiedType::fromDER($data)->asSequence();
        $permitted = null;
        $excluded = null;
        if ($seq->hasTagged(0)) {
            $permitted = GeneralSubtrees::fromASN1(
                $seq->getTagged(0)
                    ->asImplicit(Element::TYPE_SEQUENCE)->asSequence()
            );
        }
        if ($seq->hasTagged(1)) {
            $excluded = GeneralSubtrees::fromASN1(
                $seq->getTagged(1)
                    ->asImplicit(Element::TYPE_SEQUENCE)->asSequence()
            );
        }
        return self::create($critical, $permitted, $excluded);
    }

    protected function valueASN1(): Element
    {
        $elements = [];
        if (isset($this->permitted)) {
            $elements[] = ImplicitlyTaggedType::create(0, $this->permitted->toASN1());
        }
        if (isset($this->excluded)) {
            $elements[] = ImplicitlyTaggedType::create(1, $this->excluded->toASN1());
        }
        return Sequence::create(...$elements);
    }
}
