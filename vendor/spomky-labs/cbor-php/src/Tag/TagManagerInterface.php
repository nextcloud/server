<?php

declare(strict_types=1);

namespace CBOR\Tag;

use CBOR\CBORObject;

interface TagManagerInterface
{
    public function createObjectForValue(int $additionalInformation, ?string $data, CBORObject $object): TagInterface;
}
