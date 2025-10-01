<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\GeneralName;

use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements *ediPartyName* CHOICE type of *GeneralName*.
 *
 * Currently acts as a parking object for decoding.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.6
 *
 * @todo Implement EDIPartyName type
 */
final class EDIPartyName extends GeneralName
{
    private function __construct(
        protected Sequence $element
    ) {
        parent::__construct(self::TAG_EDI_PARTY_NAME);
    }

    public static function create(Sequence $element): self
    {
        return new self($element);
    }

    /**
     * @return self
     */
    public static function fromChosenASN1(UnspecifiedType $el): GeneralName
    {
        return self::create($el->asSequence());
    }

    public function string(): string
    {
        return bin2hex($this->element->toDER());
    }

    protected function choiceASN1(): TaggedType
    {
        return ImplicitlyTaggedType::create($this->tag, $this->element);
    }
}
