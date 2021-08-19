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

use ArrayIterator;
use function count;
use Countable;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;

final class MapObject extends AbstractCBORObject implements Countable, IteratorAggregate
{
    private const MAJOR_TYPE = 0b101;

    /**
     * @var MapItem[]
     */
    private $data = [];

    /**
     * @var int|null
     */
    private $length;

    /**
     * @param MapItem[] $data
     */
    public function __construct(array $data = [])
    {
        list($additionalInformation, $length) = LengthCalculator::getLengthOfArray($data);
        array_map(static function ($item): void {
            if (!$item instanceof MapItem) {
                throw new InvalidArgumentException('The list must contain only MapItem objects.');
            }
        }, $data);

        parent::__construct(self::MAJOR_TYPE, $additionalInformation);
        $this->data = $data;
        $this->length = $length;
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        if (null !== $this->length) {
            $result .= $this->length;
        }
        foreach ($this->data as $object) {
            $result .= (string) $object->getKey();
            $result .= (string) $object->getValue();
        }

        return $result;
    }

    public function add(CBORObject $key, CBORObject $value): void
    {
        $this->data[] = new MapItem($key, $value);
        list($this->additionalInformation, $this->length) = LengthCalculator::getLengthOfArray($this->data);
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->data);
    }

    public function getNormalizedData(bool $ignoreTags = false): array
    {
        $result = [];
        foreach ($this->data as $object) {
            $result[$object->getKey()->getNormalizedData($ignoreTags)] = $object->getValue()->getNormalizedData($ignoreTags);
        }

        return $result;
    }
}
