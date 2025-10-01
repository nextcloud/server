<?php

declare(strict_types=1);

namespace Cose\Algorithm\Signature\ECDSA;

use Cose\Key\Ec2Key;
use const OPENSSL_ALGO_SHA512;

final class ES512 extends ECDSA
{
    public const ID = -36;

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

    protected function getCurve(): int
    {
        return Ec2Key::CURVE_P521;
    }

    protected function getSignaturePartLength(): int
    {
        return 132;
    }
}
