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

final class TextStringObject extends AbstractCBORObject
{
    private const MAJOR_TYPE = 0b011;

    /**
     * @var int|null
     */
    private $length;

    /**
     * @var string
     */
    private $data;

    public function __construct(string $data)
    {
        list($additionalInformation, $length) = LengthCalculator::getLengthOfString($data);

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
        $result .= $this->data;

        return $result;
    }

    public function getValue(): string
    {
        return $this->data;
    }

    public function getLength(): int
    {
        return mb_strlen($this->data, 'utf8');
    }

    public function getNormalizedData(bool $ignoreTags = false): string
    {
        return $this->data;
    }
}
