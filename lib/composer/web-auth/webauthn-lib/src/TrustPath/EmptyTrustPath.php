<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn\TrustPath;

final class EmptyTrustPath implements TrustPath
{
    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => self::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromArray(array $data): TrustPath
    {
        return new EmptyTrustPath();
    }
}
