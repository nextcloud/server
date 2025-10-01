<?php

declare(strict_types=1);

namespace Cose\Algorithm\Mac;

final class HS384 extends Hmac
{
    public const ID = 6;

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
        return 'sha384';
    }

    protected function getSignatureLength(): int
    {
        return 384;
    }
}
