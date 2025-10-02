<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\DistributionPoint;

use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ExplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\X509\GeneralName\GeneralNames;

/**
 * Implements *DistributionPoint* ASN.1 type used by 'CRL Distribution Points' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.13
 */
final class DistributionPoint
{
    private function __construct(
        private readonly ?DistributionPointName $distributionPoint,
        private readonly ?ReasonFlags $reasons,
        private readonly ?GeneralNames $issuer
    ) {
    }

    public static function create(
        ?DistributionPointName $distributionPoint = null,
        ?ReasonFlags $reasons = null,
        ?GeneralNames $issuer = null
    ): self {
        return new self($distributionPoint, $reasons, $issuer);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $name = null;
        $reasons = null;
        $issuer = null;
        if ($seq->hasTagged(0)) {
            // promoted to explicit tagging because underlying type is CHOICE
            $name = DistributionPointName::fromTaggedType($seq->getTagged(0)->asExplicit()->asTagged());
        }
        if ($seq->hasTagged(1)) {
            $reasons = ReasonFlags::fromASN1(
                $seq->getTagged(1)
                    ->asImplicit(Element::TYPE_BIT_STRING)
                    ->asBitString()
            );
        }
        if ($seq->hasTagged(2)) {
            $issuer = GeneralNames::fromASN1(
                $seq->getTagged(2)
                    ->asImplicit(Element::TYPE_SEQUENCE)
                    ->asSequence()
            );
        }
        return self::create($name, $reasons, $issuer);
    }

    /**
     * Check whether distribution point name is set.
     */
    public function hasDistributionPointName(): bool
    {
        return isset($this->distributionPoint);
    }

    /**
     * Get distribution point name.
     */
    public function distributionPointName(): DistributionPointName
    {
        if (! $this->hasDistributionPointName()) {
            throw new LogicException('distributionPoint not set.');
        }
        return $this->distributionPoint;
    }

    /**
     * Check whether distribution point name is set, and it's a full name.
     */
    public function hasFullName(): bool
    {
        return $this->distributionPointName()
            ->tag() ===
            DistributionPointName::TAG_FULL_NAME;
    }

    /**
     * Get full distribution point name.
     */
    public function fullName(): FullName
    {
        if (! $this->distributionPoint instanceof FullName || ! $this->hasFullName()) {
            throw new LogicException('fullName not set.');
        }
        return $this->distributionPoint;
    }

    /**
     * Check whether distribution point name is set and it's a relative name.
     */
    public function hasRelativeName(): bool
    {
        return $this->distributionPointName()
            ->tag() ===
            DistributionPointName::TAG_RDN;
    }

    /**
     * Get relative distribution point name.
     */
    public function relativeName(): RelativeName
    {
        if (! $this->distributionPoint instanceof RelativeName || ! $this->hasRelativeName()) {
            throw new LogicException('nameRelativeToCRLIssuer not set.');
        }
        return $this->distributionPoint;
    }

    /**
     * Check whether reasons flags is set.
     */
    public function hasReasons(): bool
    {
        return isset($this->reasons);
    }

    /**
     * Get revocation reason flags.
     */
    public function reasons(): ReasonFlags
    {
        if (! $this->hasReasons()) {
            throw new LogicException('reasons not set.');
        }
        return $this->reasons;
    }

    /**
     * Check whether cRLIssuer is set.
     */
    public function hasCRLIssuer(): bool
    {
        return isset($this->issuer);
    }

    /**
     * Get CRL issuer.
     */
    public function crlIssuer(): GeneralNames
    {
        if (! $this->hasCRLIssuer()) {
            throw new LogicException('crlIssuer not set.');
        }
        return $this->issuer;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        $elements = [];
        if (isset($this->distributionPoint)) {
            $elements[] = ExplicitlyTaggedType::create(0, $this->distributionPoint->toASN1());
        }
        if (isset($this->reasons)) {
            $elements[] = ImplicitlyTaggedType::create(1, $this->reasons->toASN1());
        }
        if (isset($this->issuer)) {
            $elements[] = ImplicitlyTaggedType::create(2, $this->issuer->toASN1());
        }
        return Sequence::create(...$elements);
    }
}
