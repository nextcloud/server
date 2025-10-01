<?php

declare(strict_types=1);

namespace Cose\Algorithm\Signature\EdDSA;

use Cose\Key\Key;

final class Ed512 extends EdDSA
{
    public const ID = -261;

    public static function create(): self
    {
        return new self();
    }

    public static function identifier(): int
    {
        return self::ID;
    }

    public function sign(string $data, Key $key): string
    {
        $hashedData = hash('sha512', $data, true);

        return parent::sign($hashedData, $key);
    }

    public function verify(string $data, Key $key, string $signature): bool
    {
        $hashedData = hash('sha512', $data, true);

        return parent::verify($hashedData, $key, $signature);
    }
}
