<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli;

use Doctrine\DBAL\Driver\Exception;
use mysqli;

interface Initializer
{
    /** @throws Exception */
    public function initialize(mysqli $connection): void;
}
