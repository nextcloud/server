<?php

declare(strict_types=1);

namespace Cose\Algorithm\Signature\RSA;

use const OPENSSL_ALGO_SHA512;

final class RS512 extends RSA
{
    public const ID = -259;

    public static function create(): self
    {
        return new self();
    }

    public static function identifier(): int
    {
        return self::ID;
    }

    protected function getHashAlgorithm(): int
    {
        return OPENSSL_ALGO_SHA512;
    }
}
