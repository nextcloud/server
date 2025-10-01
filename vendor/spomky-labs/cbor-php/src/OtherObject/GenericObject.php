<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use CBOR\OtherObject as Base;
use InvalidArgumentException;
use function ord;

final class GenericObject extends Base
{
    public static function supportedAdditionalInformation(): array
    {
        return [];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        if ($data !== null && ord($data) < 32) {
            throw new InvalidArgumentException('Invalid simple value. Content data should not be present.');
        }

        return new self($additionalInformation, $data);
    }
}
