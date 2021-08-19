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

namespace Cose\Algorithm\Signature\RSA;

final class RS512 extends RSA
{
    public const ID = -259;

    public static function identifier(): int
    {
        return self::ID;
    }

    protected function getHashAlgorithm(): int
    {
        return OPENSSL_ALGO_SHA512;
    }
}
