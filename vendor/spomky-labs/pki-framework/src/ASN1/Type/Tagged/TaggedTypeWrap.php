<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Tagged;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;

/**
 * Base class to wrap inner element for tagging.
 */
abstract class TaggedTypeWrap extends TaggedType
{
    protected function __construct(
        protected readonly Element $element,
        private readonly int $class,
        int $typeTag
    ) {
        parent::__construct($typeTag);
    }

    public function typeClass(): int
    {
        return $this->class;
    }
}
