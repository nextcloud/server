<?php

declare(strict_types=1);

namespace Cose\Algorithm\Mac;

use Cose\Algorithm\Algorithm;
use Cose\Key\Key;

interface Mac extends Algorithm
{
    public function hash(string $data, Key $key): string;

    public function verify(string $data, Key $key, string $signature): bool;
}
