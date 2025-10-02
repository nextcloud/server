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

final class DoublePrecisionFloatObject extends Base implements Normalizable
{
    public static function supportedAdditionalInformation(): array
    {
        return [self::OBJECT_DOUBLE_PRECISION_FLOAT];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    public static function create(string $value): self
    {
        if (mb_strlen($value, '8bit') !== 8) {
            throw new InvalidArgumentException('The value is not a valid double precision floating point');
        }

        return new self(self::OBJECT_DOUBLE_PRECISION_FLOAT, $value);
    }

    public function normalize(): float|int
    {
        $exponent = $this->getExponent();
        $mantissa = $this->getMantissa();
        $sign = $this->getSign();

        if ($exponent === 0) {
            $val = $mantissa * 2 ** (-(1022 + 52));
        } elseif ($exponent !== 0b11111111111) {
            $val = ($mantissa + (1 << 52)) * 2 ** ($exponent - (1023 + 52));
        } else {
            $val = $mantissa === 0 ? INF : NAN;
        }

        return $sign * $val;
    }

    public function getExponent(): int
    {
        $data = $this->data;
        Utils::assertString($data, 'Invalid data');

        return Utils::binToBigInteger($data)->shiftedRight(52)->and(Utils::hexToBigInteger('7ff'))->toInt();
    }

    public function getMantissa(): int
    {
        $data = $this->data;
        Utils::assertString($data, 'Invalid data');

        return Utils::binToBigInteger($data)->and(Utils::hexToBigInteger('fffffffffffff'))->toInt();
    }

    public function getSign(): int
    {
        $data = $this->data;
        Utils::assertString($data, 'Invalid data');
        $sign = Utils::binToBigInteger($data)->shiftedRight(63);

        return $sign->isEqualTo(BigInteger::one()) ? -1 : 1;
    }
}
