<?php

declare(strict_types=1);

namespace Cose\Algorithm\Signature\RSA;

use Cose\Hash;

final class PS256 extends PSSRSA
{
    public const ID = -37;

    public static function create(): self
    {
        return new self();
    }

    public static function identifier(): int
    {
        return self::ID;
    }

    protected function getHashAlgorithm(): Hash
    {
        return Hash::sha256();
    }
}
