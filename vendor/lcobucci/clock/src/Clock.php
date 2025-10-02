<?php
declare(strict_types=1);

namespace Lcobucci\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

interface Clock extends ClockInterface
{
    public function now(): DateTimeImmutable;
}
