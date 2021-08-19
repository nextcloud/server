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

use function chr;

abstract class AbstractCBORObject implements CBORObject
{
    /**
     * @var int
     */
    protected $additionalInformation;
    /**
     * @var int
     */
    private $majorType;

    public function __construct(int $majorType, int $additionalInformation)
    {
        $this->majorType = $majorType;
        $this->additionalInformation = $additionalInformation;
    }

    public function __toString(): string
    {
        return chr($this->majorType << 5 | $this->additionalInformation);
    }

    public function getMajorType(): int
    {
        return $this->majorType;
    }

    public function getAdditionalInformation(): int
    {
        return $this->additionalInformation;
    }
}
