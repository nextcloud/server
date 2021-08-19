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

use Brick\Math\BigInteger;
use InvalidArgumentException;

final class UnsignedIntegerObject extends AbstractCBORObject
{
    private const MAJOR_TYPE = 0b000;

    /**
     * @var string|null
     */
    private $data;

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

    public static function createObjectForValue(int $additionalInformation, ?string $data): self
    {
        return new self($additionalInformation, $data);
    }

    public static function create(int $value): self
    {
        return self::createFromString((string) $value);
    }

    public static function createFromHex(string $value): self
    {
        $integer = BigInteger::fromBase($value, 16);

        return self::createBigInteger($integer);
    }

    public static function createFromString(string $value): self
    {
        $integer = BigInteger::of($value);

        return self::createBigInteger($integer);
    }

    public function getMajorType(): int
    {
        return self::MAJOR_TYPE;
    }

    public function getAdditionalInformation(): int
    {
        return $this->additionalInformation;
    }

    public function getValue(): string
    {
        return $this->getNormalizedData();
    }

    public function getNormalizedData(bool $ignoreTags = false): string
    {
        if (null === $this->data) {
            return (string) $this->additionalInformation;
        }

        $integer = BigInteger::fromBase(bin2hex($this->data), 16);

        return $integer->toBase(10);
    }

    private static function createBigInteger(BigInteger $integer): self
    {
        if ($integer->isLessThan(BigInteger::zero())) {
            throw new InvalidArgumentException('The value must be a positive integer.');
        }

        switch (true) {
            case $integer->isLessThan(BigInteger::of(24)):
                $ai = $integer->toInt();
                $data = null;
                break;
            case $integer->isLessThan(BigInteger::fromBase('FF', 16)):
                $ai = 24;
                $data = self::hex2bin(str_pad($integer->toBase(16), 2, '0', STR_PAD_LEFT));
                break;
            case $integer->isLessThan(BigInteger::fromBase('FFFF', 16)):
                $ai = 25;
                $data = self::hex2bin(str_pad($integer->toBase(16), 4, '0', STR_PAD_LEFT));
                break;
            case $integer->isLessThan(BigInteger::fromBase('FFFFFFFF', 16)):
                $ai = 26;
                $data = self::hex2bin(str_pad($integer->toBase(16), 8, '0', STR_PAD_LEFT));
                break;
            default:
                throw new InvalidArgumentException('Out of range. Please use PositiveBigIntegerTag tag with ByteStringObject object instead.');
        }

        return new self($ai, $data);
    }

    private static function hex2bin(string $data): string
    {
        $result = hex2bin($data);
        if (false === $result) {
            throw new InvalidArgumentException('Unable to convert the data');
        }

        return $result;
    }
}
