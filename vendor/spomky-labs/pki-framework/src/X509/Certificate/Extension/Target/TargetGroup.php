<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\Target;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ExplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;
use SpomkyLabs\Pki\X509\GeneralName\GeneralName;

/**
 * Implements 'targetGroup' CHOICE of the *Target* ASN.1 type.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.3.2
 */
final class TargetGroup extends Target
{
    private function __construct(
        private readonly GeneralName $name
    ) {
        parent::__construct(self::TYPE_GROUP);
    }

    public static function create(GeneralName $name): self
    {
        return new self($name);
    }

    /**
     * @return self
     */
    public static function fromChosenASN1(TaggedType $el): Target
    {
        return self::create(GeneralName::fromASN1($el));
    }

    public function string(): string
    {
        return $this->name->string();
    }

    /**
     * Get group name.
     */
    public function name(): GeneralName
    {
        return $this->name;
    }

    public function toASN1(): Element
    {
        return ExplicitlyTaggedType::create($this->type, $this->name->toASN1());
    }
}
