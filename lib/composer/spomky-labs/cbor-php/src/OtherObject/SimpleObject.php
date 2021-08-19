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

namespace CBOR\OtherObject;

use CBOR\OtherObject as Base;
use CBOR\Utils;
use function chr;
use InvalidArgumentException;

final class SimpleObject extends Base
{
    public static function supportedAdditionalInformation(): array
    {
        return [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 24];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    public function getNormalizedData(bool $ignoreTags = false)
    {
        if (null === $this->data) {
            return $this->getAdditionalInformation();
        }

        return Utils::binToInt($this->data);
    }

    /**
     * @return SimpleObject
     */
    public static function create(int $value): self
    {
        switch (true) {
            case $value < 24:
                return new self($value, null);
            case $value < 256:
                return new self(24, chr($value));
            default:
                throw new InvalidArgumentException('The value is not a valid simple value');
        }
    }
}
