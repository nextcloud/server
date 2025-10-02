<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use Brick\Math\BigInteger;
use CBOR\OtherObject as Base;
use CBOR\Utils;
use InvalidArgumentException;
use const INF;
use const NAN;

final class SinglePrecisionFloatObject extends Base
{
    public static function supportedAdditionalInformation(): array
    {
        return [self::OBJECT_SINGLE_PRECISION_FLOAT];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    public static function create(string $value): self
    {
        if (mb_strlen($value, '8bit') !== 4) {
            throw new InvalidArgumentException('The value is not a valid single precision floating point');
        }

        return new self(self::OBJECT_SINGLE_PRECISION_FLOAT, $value);
    }

    public function normalize(): float|int
    {
        $exponent = $this->getExponent();
        $mantissa = $this->getMantissa();
        $sign = $this->getSign();

        if ($exponent === 0) {
            $val = $mantissa * 2 ** (-(126 + 23));
        } elseif ($exponent !== 0b11111111) {
            $val = ($mantissa + (1 << 23)) * 2 ** ($exponent - (127 + 23));
        } else {
            $val = $mantissa === 0 ? INF : NAN;
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
        $sign = Utils::binToBigInteger($data)->shiftedRight(31);

        return $sign->isEqualTo(BigInteger::one()) ? -1 : 1;
    }
}
