<?php

declare(strict_types=1);

namespace Cose\Algorithm\Mac;

final class HS256 extends Hmac
{
    public const ID = 5;

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
        return 'sha256';
    }

    protected function getSignatureLength(): int
    {
        return 256;
    }
}
