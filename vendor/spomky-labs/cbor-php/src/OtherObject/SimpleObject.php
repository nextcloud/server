<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use CBOR\Normalizable;
use CBOR\OtherObject as Base;
use CBOR\Utils;
use InvalidArgumentException;
use function chr;
use function ord;

final class SimpleObject extends Base implements Normalizable
{
    public static function supportedAdditionalInformation(): array
    {
        return array_merge(range(0, 19), [24]);
    }

    public static function create(int $value): self|FalseObject|TrueObject|NullObject|UndefinedObject
    {
        switch (true) {
            case $value >= 0 && $value <= 19:
                return new self($value, null);
            case $value === 20:
                return FalseObject::create();
            case $value === 21:
                return TrueObject::create();
            case $value === 22:
                return NullObject::create();
            case $value === 23:
                return UndefinedObject::create();
            case $value <= 31:
                throw new InvalidArgumentException('Invalid simple value. Shall be between 32 and 255.');
            case $value <= 255:
                return new self(24, chr($value));
            default:
                throw new InvalidArgumentException('The value is not a valid simple value.');
        }
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        if ($additionalInformation === 24) {
            if ($data === null) {
                throw new InvalidArgumentException('Invalid simple value. Content data is missing.');
            }
            if (mb_strlen($data, '8bit') !== 1) {
                throw new InvalidArgumentException('Invalid simple value. Content data is too long.');
            }
            if (ord($data) < 32) {
                throw new InvalidArgumentException('Invalid simple value. Content data must be between 32 and 255.');
            }
        } elseif ($additionalInformation < 20) {
            if ($data !== null) {
                throw new InvalidArgumentException('Invalid simple value. Content data should not be present.');
            }
        }

        return new self($additionalInformation, $data);
    }

    public function normalize(): int
    {
        if ($this->data === null) {
            return $this->getAdditionalInformation();
        }

        return Utils::binToInt($this->data);
    }
}
