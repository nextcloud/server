<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\Target;

use RuntimeException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;
use UnexpectedValueException;

/**
 * Base class for *Target* ASN.1 CHOICE type.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.3.2
 */
abstract class Target
{
    public const TYPE_NAME = 0;

    public const TYPE_GROUP = 1;

    public const TYPE_CERT = 2;

    protected function __construct(
        protected int $type
    ) {
    }

    /**
     * Generate ASN.1 element.
     */
    abstract public function toASN1(): Element;

    /**
     * Get string value of the target.
     */
    abstract public function string(): string;

    /**
     * Initialize concrete object from the chosen ASN.1 element.
     */
    abstract public static function fromChosenASN1(TaggedType $el): self;

    /**
     * Parse from ASN.1.
     */
    public static function fromASN1(TaggedType $el): self
    {
        return match ($el->tag()) {
            self::TYPE_NAME => TargetName::fromChosenASN1($el->asExplicit()->asTagged()),
            self::TYPE_GROUP => TargetGroup::fromChosenASN1($el->asExplicit()->asTagged()),
            self::TYPE_CERT => throw new RuntimeException('targetCert not supported.'),
            default => throw new UnexpectedValueException('Target type ' . $el->tag() . ' not supported.'),
        };
    }

    /**
     * Get type tag.
     */
    public function type(): int
    {
        return $this->type;
    }

    /**
     * Check whether target is equal to another.
     */
    public function equals(self $other): bool
    {
        if ($this->type !== $other->type) {
            return false;
        }
        if ($this->toASN1()->toDER() !== $other->toASN1()->toDER()) {
            return false;
        }
        return true;
    }
}
