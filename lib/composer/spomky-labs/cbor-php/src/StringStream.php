<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR;

use InvalidArgumentException;
use RuntimeException;

final class StringStream implements Stream
{
    /**
     * @var resource
     */
    private $resource;

    public function __construct(string $data)
    {
        $resource = fopen('php://memory', 'rb+');
        if (false === $resource) {
            throw new RuntimeException('Unable to open the memory');
        }
        $result = fwrite($resource, $data);
        if (false === $result) {
            throw new RuntimeException('Unable to write the memory');
        }
        $result = rewind($resource);
        if (false === $result) {
            throw new RuntimeException('Unable to rewind the memory');
        }
        $this->resource = $resource;
    }

    public function read(int $length): string
    {
        if (0 === $length) {
            return '';
        }
        $data = fread($this->resource, $length);
        if (false === $data) {
            throw new RuntimeException('Unable to read the memory');
        }
        if (mb_strlen($data, '8bit') !== $length) {
            throw new InvalidArgumentException(sprintf('Out of range. Expected: %d, read: %d.', $length, mb_strlen($data, '8bit')));
        }

        return $data;
    }
}
