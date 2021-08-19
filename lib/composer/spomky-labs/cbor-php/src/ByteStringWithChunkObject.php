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

final class ByteStringWithChunkObject extends AbstractCBORObject
{
    private const MAJOR_TYPE = 0b010;
    private const ADDITIONAL_INFORMATION = 0b00011111;

    /**
     * @var ByteStringObject[]
     */
    private $chunks = [];

    public function __construct()
    {
        parent::__construct(self::MAJOR_TYPE, self::ADDITIONAL_INFORMATION);
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        foreach ($this->chunks as $chunk) {
            $result .= (string) $chunk;
        }
        $bin = hex2bin('FF');
        if (false === $bin) {
            throw new InvalidArgumentException('Unable to convert the data');
        }
        $result .= $bin;

        return $result;
    }

    public function add(ByteStringObject $chunk): void
    {
        $this->chunks[] = $chunk;
    }

    public function append(string $chunk): void
    {
        $this->add(new ByteStringObject($chunk));
    }

    public function getValue(): string
    {
        $result = '';
        foreach ($this->chunks as $chunk) {
            $result .= $chunk->getValue();
        }

        return $result;
    }

    public function getLength(): int
    {
        $length = 0;
        foreach ($this->chunks as $chunk) {
            $length += $chunk->getLength();
        }

        return $length;
    }

    public function getNormalizedData(bool $ignoreTags = false): string
    {
        $result = '';
        foreach ($this->chunks as $chunk) {
            $result .= $chunk->getNormalizedData($ignoreTags);
        }

        return $result;
    }
}
