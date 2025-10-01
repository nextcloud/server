<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\GeneralName;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectIdentifier;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ExplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements *otherName* CHOICE type of *GeneralName*.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.6
 */
final class OtherName extends GeneralName
{
    /**
     * @param string $type OID
     */
    private function __construct(
        private readonly string $type,
        private readonly Element $element
    ) {
        parent::__construct(self::TAG_OTHER_NAME);
    }

    public static function create(string $type, Element $element): self
    {
        return new self($type, $element);
    }

    /**
     * @return self
     */
    public static function fromChosenASN1(UnspecifiedType $el): GeneralName
    {
        $seq = $el->asSequence();
        $type_id = $seq->at(0)
            ->asObjectIdentifier()
            ->oid();
        $value = $seq->getTagged(0)
            ->asExplicit()
            ->asElement();
        return self::create($type_id, $value);
    }

    public function string(): string
    {
        return $this->type . '/#' . bin2hex($this->element->toDER());
    }

    /**
     * Get type OID.
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Get value element.
     */
    public function value(): Element
    {
        return $this->element;
    }

    protected function choiceASN1(): TaggedType
    {
        return ImplicitlyTaggedType::create(
            $this->tag,
            Sequence::create(ObjectIdentifier::create($this->type), ExplicitlyTaggedType::create(0, $this->element))
        );
    }
}
