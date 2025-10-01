<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use CBOR\Normalizable;
use CBOR\OtherObject as Base;

final class TrueObject extends Base implements Normalizable
{
    public function __construct()
    {
        parent::__construct(self::OBJECT_TRUE, null);
    }

    public static function create(): self
    {
        return new self();
    }

    public static function supportedAdditionalInformation(): array
    {
        return [self::OBJECT_TRUE];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self();
    }

    public function normalize(): bool
    {
        return true;
    }
}
