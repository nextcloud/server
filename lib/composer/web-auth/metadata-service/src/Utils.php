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

namespace Webauthn\MetadataService;

use LogicException;
use Throwable;

/**
 * @internal
 */
abstract class Utils
{
    public static function logicException(string $message, ?Throwable $previousException = null): callable
    {
        return static function () use ($message, $previousException): LogicException {
            return new LogicException($message, 0, $previousException);
        };
    }

    public static function filterNullValues(array $data): array
    {
        return array_filter($data, static function ($var): bool {return null !== $var; });
    }
}
