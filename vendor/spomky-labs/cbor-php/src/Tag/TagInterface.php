<?php

declare(strict_types=1);

namespace CBOR\Tag;

use CBOR\CBORObject;

interface TagInterface extends CBORObject
{
    public static function getTagId(): int;

    public function getValue(): CBORObject;

    public static function createFromLoadedData(
        int $additionalInformation,
        ?string $data,
        CBORObject $object
    ): self;
}
