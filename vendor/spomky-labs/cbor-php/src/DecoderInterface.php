<?php

declare(strict_types=1);

namespace CBOR;

interface DecoderInterface
{
    public function decode(Stream $stream): CBORObject;
}
