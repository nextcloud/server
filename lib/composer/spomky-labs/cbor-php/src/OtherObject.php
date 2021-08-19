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

abstract class OtherObject extends AbstractCBORObject
{
    private const MAJOR_TYPE = 0b111;

    /**
     * @var string|null
     */
    protected $data;

    public function __construct(int $additionalInformation, ?string $data)
    {
        parent::__construct(self::MAJOR_TYPE, $additionalInformation);
        $this->data = $data;
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        if (null !== $this->data) {
            $result .= $this->data;
        }

        return $result;
    }

    /**
     * @return int[]
     */
    abstract public static function supportedAdditionalInformation(): array;

    abstract public static function createFromLoadedData(int $additionalInformation, ?string $data): self;
}
