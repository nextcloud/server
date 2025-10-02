<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\GeneralName;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use Stringable;
use UnexpectedValueException;

/**
 * Implements *GeneralName* CHOICE with implicit tagging.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.6
 */
abstract class GeneralName implements Stringable
{
    // GeneralName CHOICE tags
    public const TAG_OTHER_NAME = 0;

    public const TAG_RFC822_NAME = 1;

    public const TAG_DNS_NAME = 2;

    public const TAG_X400_ADDRESS = 3;

    public const TAG_DIRECTORY_NAME = 4;

    public const TAG_EDI_PARTY_NAME = 5;

    public const TAG_URI = 6;

    public const TAG_IP_ADDRESS = 7;

    public const TAG_REGISTERED_ID = 8;

    protected function __construct(
        protected int $tag
    ) {
    }

    /**
     * Get general name as a string.
     */
    public function __toString(): string
    {
        return $this->string();
    }

    /**
     * Get string value of the type.
     */
    abstract public function string(): string;

    /**
     * Initialize concrete object from the chosen ASN.1 element.
     */
    abstract public static function fromChosenASN1(UnspecifiedType $el): self;

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(TaggedType $el): self
    {
        return match ($el->tag()) {
            self::TAG_OTHER_NAME => OtherName::fromChosenASN1($el->asImplicit(Element::TYPE_SEQUENCE)),
            self::TAG_RFC822_NAME => RFC822Name::fromChosenASN1($el->asImplicit(Element::TYPE_IA5_STRING)),
            self::TAG_DNS_NAME => DNSName::fromChosenASN1($el->asImplicit(Element::TYPE_IA5_STRING)),
            self::TAG_X400_ADDRESS => X400Address::fromChosenASN1($el->asImplicit(Element::TYPE_SEQUENCE)),
            self::TAG_DIRECTORY_NAME => DirectoryName::fromChosenASN1($el->asExplicit()),
            self::TAG_EDI_PARTY_NAME => EDIPartyName::fromChosenASN1($el->asImplicit(Element::TYPE_SEQUENCE)),
            self::TAG_URI => UniformResourceIdentifier::fromChosenASN1($el->asImplicit(Element::TYPE_IA5_STRING)),
            self::TAG_IP_ADDRESS => IPAddress::fromChosenASN1($el->asImplicit(Element::TYPE_OCTET_STRING)),
            self::TAG_REGISTERED_ID => RegisteredID::fromChosenASN1($el->asImplicit(Element::TYPE_OBJECT_IDENTIFIER)),
            default => throw new UnexpectedValueException('GeneralName type ' . $el->tag() . ' not supported.'),
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
     * Generate ASN.1 element.
     */
    public function toASN1(): Element
    {
        return $this->choiceASN1();
    }

    /**
     * Check whether GeneralName is equal to others.
     *
     * @param GeneralName $other GeneralName to compare to
     *
     * @return bool True if names are equal
     */
    public function equals(self $other): bool
    {
        if ($this->tag !== $other->tag) {
            return false;
        }
        if ($this->choiceASN1()->toDER() !== $other->choiceASN1()->toDER()) {
            return false;
        }
        return true;
    }

    /**
     * Get ASN.1 value in GeneralName CHOICE context.
     */
    abstract protected function choiceASN1(): TaggedType;
}
