<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements 'Policy Constraints' certificate extensions.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.11
 */
final class PolicyConstraintsExtension extends Extension
{
    private function __construct(
        bool $critical,
        private readonly ?int $requireExplicitPolicy,
        private readonly ?int $inhibitPolicyMapping
    ) {
        parent::__construct(self::OID_POLICY_CONSTRAINTS, $critical);
    }

    public static function create(
        bool $critical,
        ?int $requireExplicitPolicy = null,
        ?int $inhibitPolicyMapping = null
    ): self {
        return new self($critical, $requireExplicitPolicy, $inhibitPolicyMapping);
    }

    /**
     * Whether requireExplicitPolicy is present.
     */
    public function hasRequireExplicitPolicy(): bool
    {
        return isset($this->requireExplicitPolicy);
    }

    public function requireExplicitPolicy(): int
    {
        if (! $this->hasRequireExplicitPolicy()) {
            throw new LogicException('requireExplicitPolicy not set.');
        }
        return $this->requireExplicitPolicy;
    }

    /**
     * Whether inhibitPolicyMapping is present.
     */
    public function hasInhibitPolicyMapping(): bool
    {
        return isset($this->inhibitPolicyMapping);
    }

    public function inhibitPolicyMapping(): int
    {
        if (! $this->hasInhibitPolicyMapping()) {
            throw new LogicException('inhibitPolicyMapping not set.');
        }
        return $this->inhibitPolicyMapping;
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        $seq = UnspecifiedType::fromDER($data)->asSequence();
        $require_explicit_policy = null;
        $inhibit_policy_mapping = null;
        if ($seq->hasTagged(0)) {
            $require_explicit_policy = $seq->getTagged(0)
                ->asImplicit(Element::TYPE_INTEGER)->asInteger()->intNumber();
        }
        if ($seq->hasTagged(1)) {
            $inhibit_policy_mapping = $seq->getTagged(1)
                ->asImplicit(Element::TYPE_INTEGER)->asInteger()->intNumber();
        }
        return self::create($critical, $require_explicit_policy, $inhibit_policy_mapping);
    }

    protected function valueASN1(): Element
    {
        $elements = [];
        if (isset($this->requireExplicitPolicy)) {
            $elements[] = ImplicitlyTaggedType::create(0, Integer::create($this->requireExplicitPolicy));
        }
        if (isset($this->inhibitPolicyMapping)) {
            $elements[] = ImplicitlyTaggedType::create(1, Integer::create($this->inhibitPolicyMapping));
        }
        return Sequence::create(...$elements);
    }
}
