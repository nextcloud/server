<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\GeneralName;

use SpomkyLabs\Pki\ASN1\Type\Primitive\IA5String;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements *uniformResourceIdentifier* CHOICE type of *GeneralName*.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.6
 */
final class UniformResourceIdentifier extends GeneralName
{
    private function __construct(
        private readonly string $uri
    ) {
        parent::__construct(self::TAG_URI);
    }

    public static function create(string $uri): self
    {
        return new self($uri);
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
        return $this->uri;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    protected function choiceASN1(): TaggedType
    {
        return ImplicitlyTaggedType::create($this->tag, IA5String::create($this->uri));
    }
}
