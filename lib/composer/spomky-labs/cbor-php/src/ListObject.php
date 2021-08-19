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

use function array_key_exists;
use ArrayIterator;
use function count;
use Countable;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;

class ListObject extends AbstractCBORObject implements Countable, IteratorAggregate
{
    private const MAJOR_TYPE = 0b100;

    /**
     * @var CBORObject[]
     */
    private $data = [];

    /**
     * @var int|null
     */
    private $length;

    /**
     * @param CBORObject[] $data
     */
    public function __construct(array $data = [])
    {
        list($additionalInformation, $length) = LengthCalculator::getLengthOfArray($data);
        array_map(static function ($item): void {
            if (!$item instanceof CBORObject) {
                throw new InvalidArgumentException('The list must contain only CBORObject objects.');
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
            $result .= (string) $object;
        }

        return $result;
    }

    public function add(CBORObject $object): void
    {
        $this->data[] = $object;
        list($this->additionalInformation, $this->length) = LengthCalculator::getLengthOfArray($this->data);
    }

    public function get(int $index): CBORObject
    {
        if (!array_key_exists($index, $this->data)) {
            throw new InvalidArgumentException('Index not found.');
        }

        return $this->data[$index];
    }

    public function getNormalizedData(bool $ignoreTags = false): array
    {
        return array_map(function (CBORObject $item) use ($ignoreTags) {
            return $item->getNormalizedData($ignoreTags);
        }, $this->data);
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->data);
    }
}
