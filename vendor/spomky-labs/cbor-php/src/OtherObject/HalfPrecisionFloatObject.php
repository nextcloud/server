<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use Brick\Math\BigInteger;
use CBOR\Normalizable;
use CBOR\OtherObject as Base;
use CBOR\Utils;
use InvalidArgumentException;
use const INF;
use const NAN;

final class HalfPrecisionFloatObject extends Base implements Normalizable
{
    public static function supportedAdditionalInformation(): array
    {
        return [self::OBJECT_HALF_PRECISION_FLOAT];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    public static function create(string $value): self
    {
        if (mb_strlen($value, '8bit') !== 2) {
            throw new InvalidArgumentException('The value is not a valid half precision floating point');
        }

        return new self(self::OBJECT_HALF_PRECISION_FLOAT, $value);
    }

    public function normalize(): float|int
    {
        $exponent = $this->getExponent();
        $mantissa = $this->getMantissa();
        $sign = $this->getSign();

        if ($exponent === 0) {
            $val = $mantissa * 2 ** (-24);
        } elseif ($exponent !== 0b11111) {
            $val = ($mantissa + (1 << 10)) * 2 ** ($exponent - 25);
        } else {
            $val = $mantissa === 0 ? INF : NAN;
        }

        return $sign * $val;
    }

    public function getExponent(): int
    {
        $data = $this->data;
        Utils::assertString($data, 'Invalid data');

        return Utils::binToBigInteger($data)->shiftedRight(10)->and(Utils::hexToBigInteger('1f'))->toInt();
    }

    public function getMantissa(): int
    {
        $data = $this->data;
        Utils::assertString($data, 'Invalid data');

        return Utils::binToBigInteger($data)->and(Utils::hexToBigInteger('3ff'))->toInt();
    }

    public function getSign(): int
    {
        $data = $this->data;
        Utils::assertString($data, 'Invalid data');
        $sign = Utils::binToBigInteger($data)->shiftedRight(15);

        return $sign->isEqualTo(BigInteger::one()) ? -1 : 1;
    }
}
