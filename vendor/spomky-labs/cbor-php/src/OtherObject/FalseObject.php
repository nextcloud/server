<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use CBOR\Normalizable;
use CBOR\OtherObject as Base;

final class FalseObject extends Base implements Normalizable
{
    public function __construct()
    {
        parent::__construct(self::OBJECT_FALSE, null);
    }

    public static function create(): self
    {
        return new self();
    }

    public static function supportedAdditionalInformation(): array
    {
        return [self::OBJECT_FALSE];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self();
    }

    public function normalize(): bool
    {
        return false;
    }
}
