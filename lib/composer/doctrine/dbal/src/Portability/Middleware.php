<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Portability;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;

final class Middleware implements MiddlewareInterface
{
    /** @var int */
    private $mode;

    /** @var int */
    private $case;

    public function __construct(int $mode, int $case)
    {
        $this->mode = $mode;
        $this->case = $case;
    }

    public function wrap(DriverInterface $driver): DriverInterface
    {
        if ($this->mode !== 0) {
            return new Driver($driver, $this->mode, $this->case);
        }

        return $driver;
    }
}
