<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\GeneralName;

use SpomkyLabs\Pki\ASN1\Type\Primitive\IA5String;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements *dNSName* CHOICE type of *GeneralName*.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.6
 */
final class DNSName extends GeneralName
{
    /**
     * @param string $name Domain name
     */
    private function __construct(
        private readonly string $name
    ) {
        parent::__construct(self::TAG_DNS_NAME);
    }

    public static function create(string $name): self
    {
        return new self($name);
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
        return $this->name;
    }

    /**
     * Get DNS name.
     */
    public function name(): string
    {
        return $this->name;
    }

    protected function choiceASN1(): TaggedType
    {
        return ImplicitlyTaggedType::create($this->tag, IA5String::create($this->name));
    }
}
