<?php

declare(strict_types=1);

namespace CBOR;

interface Stream
{
    public function read(int $length): string;
}
