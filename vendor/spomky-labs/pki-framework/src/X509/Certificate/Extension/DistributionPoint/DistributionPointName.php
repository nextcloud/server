<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\DistributionPoint;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;
use SpomkyLabs\Pki\X501\ASN1\RDN;
use SpomkyLabs\Pki\X509\GeneralName\GeneralNames;
use UnexpectedValueException;

/**
 * Base class for *DistributionPointName* ASN.1 CHOICE type used by 'CRL Distribution Points' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.13
 */
abstract class DistributionPointName
{
    public const TAG_FULL_NAME = 0;

    public const TAG_RDN = 1;

    protected function __construct(
        protected int $tag
    ) {
    }

    /**
     * Initialize from TaggedType.
     */
    public static function fromTaggedType(TaggedType $el): self
    {
        return match ($el->tag()) {
            self::TAG_FULL_NAME => FullName::create(GeneralNames::fromASN1(
                $el->asImplicit(Element::TYPE_SEQUENCE)->asSequence()
            )),
            self::TAG_RDN => RelativeName::create(RDN::fromASN1($el->asImplicit(Element::TYPE_SET)->asSet())),
            default => throw new UnexpectedValueException(
                'DistributionPointName tag ' . $el->tag() . ' not supported.'
            ),
        };
    }

    /**
     * Get type tag.
     */
    public function tag(): int
    {
        return $this->tag;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): ImplicitlyTaggedType
    {
        return ImplicitlyTaggedType::create($this->tag, $this->_valueASN1());
    }

    /**
     * Generate ASN.1 element.
     */
    abstract protected function _valueASN1(): Element;
}
