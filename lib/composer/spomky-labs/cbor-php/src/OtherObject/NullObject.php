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

final class NullObject extends Base
{
    public function __construct()
    {
        parent::__construct(22, null);
    }

    public static function supportedAdditionalInformation(): array
    {
        return [22];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self();
    }

    public function getNormalizedData(bool $ignoreTags = false)
    {
    }
}
