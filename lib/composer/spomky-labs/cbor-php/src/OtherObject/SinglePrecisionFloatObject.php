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

use Brick\Math\BigInteger;
use CBOR\OtherObject as Base;
use CBOR\Utils;
use InvalidArgumentException;

final class SinglePrecisionFloatObject extends Base
{
    public static function supportedAdditionalInformation(): array
    {
        return [26];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    /**
     * @return SinglePrecisionFloatObject
     */
    public static function create(string $value): self
    {
        if (4 !== mb_strlen($value, '8bit')) {
            throw new InvalidArgumentException('The value is not a valid single precision floating point');
        }

        return new self(26, $value);
    }

    public function getNormalizedData(bool $ignoreTags = false)
    {
        $exp = $this->getExponent();
        $mant = $this->getMantissa();
        $sign = $this->getSign();

        if (0 === $exp) {
            $val = $mant * 2 ** (-(126 + 23));
        } elseif (0b11111111 !== $exp) {
            $val = ($mant + (1 << 23)) * 2 ** ($exp - (127 + 23));
        } else {
            $val = 0 === $mant ? INF : NAN;
        }

        return $sign * $val;
    }

    public function getExponent(): int
    {
        $data = $this->data;
        Utils::assertString($data, 'Invalid data');

        return Utils::binToBigInteger($data)->shiftedRight(23)->and(Utils::hexToBigInteger('ff'))->toInt();
    }

    public function getMantissa(): int
    {
        $data = $this->data;
        Utils::assertString($data, 'Invalid data');

        return Utils::binToBigInteger($data)->and(Utils::hexToBigInteger('7fffff'))->toInt();
    }

    public function getSign(): int
    {
        $data = $this->data;
        Utils::assertString($data, 'Invalid data');
        $sign = Utils::binToBigInteger($data)->shiftedRight(32);

        return $sign->isEqualTo(BigInteger::one()) ? -1 : 1;
    }
}
