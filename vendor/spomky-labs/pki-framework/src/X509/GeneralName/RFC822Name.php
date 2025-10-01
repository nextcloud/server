<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\GeneralName;

use SpomkyLabs\Pki\ASN1\Type\Primitive\IA5String;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements *rfc822Name* CHOICE type of *GeneralName*.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.6
 */
final class RFC822Name extends GeneralName
{
    private function __construct(
        private readonly string $email
    ) {
        parent::__construct(self::TAG_RFC822_NAME);
    }

    public static function create(string $email): self
    {
        return new self($email);
    }

    /**
     * @return self
     */
    public static function fromChosenASN1(UnspecifiedType $el): GeneralName
    {
        return self::create($el->asIA5String()->string());
    }

    public function string(): string
    {
        return $this->email;
    }

    public function email(): string
    {
        return $this->email;
    }

    protected function choiceASN1(): TaggedType
    {
        return ImplicitlyTaggedType::create($this->tag, IA5String::create($this->email));
    }
}
