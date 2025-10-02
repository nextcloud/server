<?php

declare(strict_types=1);

namespace CBOR;

use Stringable;
use function chr;

abstract class AbstractCBORObject implements CBORObject, Stringable
{
    public function __construct(
        private int $majorType,
        protected int $additionalInformation
    ) {
    }

    public function __toString(): string
    {
        return chr($this->majorType << 5 | $this->additionalInformation);
    }

    public function getMajorType(): int
    {
        return $this->majorType;
    }

    public function getAdditionalInformation(): int
    {
        return $this->additionalInformation;
    }
}
