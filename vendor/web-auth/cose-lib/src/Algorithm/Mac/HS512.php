<?php

declare(strict_types=1);

namespace Cose\Algorithm\Mac;

final class HS512 extends Hmac
{
    public const ID = 7;

    public static function create(): self
    {
        return new self();
    }

    public static function identifier(): int
    {
        return self::ID;
    }

    protected function getHashAlgorithm(): string
    {
        return 'sha512';
    }

    protected function getSignatureLength(): int
    {
        return 512;
    }
}
