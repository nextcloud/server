<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\GeneralName;

use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectIdentifier;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements *registeredID* CHOICE type of *GeneralName*.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.6
 */
final class RegisteredID extends GeneralName
{
    /**
     * @param string $oid OID in dotted format
     */
    private function __construct(
        private readonly string $oid
    ) {
        parent::__construct(self::TAG_REGISTERED_ID);
    }

    public static function create(string $oid): self
    {
        return new self($oid);
    }

    /**
     * @return self
     */
    public static function fromChosenASN1(UnspecifiedType $el): GeneralName
    {
        return self::create($el->asObjectIdentifier()->oid());
    }

    public function string(): string
    {
        return $this->oid;
    }

    /**
     * Get object identifier in dotted format.
     *
     * @return string OID
     */
    public function oid(): string
    {
        return $this->oid;
    }

    protected function choiceASN1(): TaggedType
    {
        return ImplicitlyTaggedType::create($this->tag, ObjectIdentifier::create($this->oid));
    }
}
