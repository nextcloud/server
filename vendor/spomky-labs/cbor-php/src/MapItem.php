<?php

declare(strict_types=1);

namespace CBOR;

class MapItem
{
    public function __construct(
        private CBORObject $key,
        private CBORObject $value
    ) {
    }

    public static function create(CBORObject $key, CBORObject $value): self
    {
        return new self($key, $value);
    }

    public function getKey(): CBORObject
    {
        return $this->key;
    }

    public function getValue(): CBORObject
    {
        return $this->value;
    }
}
