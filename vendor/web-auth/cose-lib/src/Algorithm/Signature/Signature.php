<?php

declare(strict_types=1);

namespace Cose\Algorithm\Signature;

use Cose\Algorithm\Algorithm;
use Cose\Key\Key;

interface Signature extends Algorithm
{
    public function sign(string $data, Key $key): string;

    public function verify(string $data, Key $key, string $signature): bool;
}
