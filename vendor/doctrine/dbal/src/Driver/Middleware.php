<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver;

use Doctrine\DBAL\Driver;

interface Middleware
{
    public function wrap(Driver $driver): Driver;
}
