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

namespace Cose\Algorithm\Signature;

use Cose\Algorithm\Algorithm;
use Cose\Key\Key;

interface Signature extends Algorithm
{
    public function sign(string $data, Key $key): string;

    public function verify(string $data, Key $key, string $signature): bool;
}
