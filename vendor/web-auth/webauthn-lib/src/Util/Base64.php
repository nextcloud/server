<?php

declare(strict_types=1);

namespace Webauthn\Util;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Throwable;
use Webauthn\Exception\InvalidDataException;

abstract class Base64
{
    public static function decode(string $data): string
    {
        try {
            return Base64UrlSafe::decode($data);
        } catch (Throwable) {
        }

        try {
            return \ParagonIE\ConstantTime\Base64::decode($data);
        } catch (Throwable $e) {
            throw InvalidDataException::create($data, 'Invalid data submitted', $e);
        }
    }
}
