<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Portability;

use Doctrine\DBAL\ColumnCase;
use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;

final class Middleware implements MiddlewareInterface
{
    private int $mode;

    /** @var 0|ColumnCase::LOWER|ColumnCase::UPPER */
    private int $case;

    /**
     * @param 0|ColumnCase::LOWER|ColumnCase::UPPER $case Determines how the column case will be treated.
     *                                                    0: The case will be left as is in the database.
     *                                                    {@see ColumnCase::LOWER}: The case will be lowercased.
     *                                                    {@see ColumnCase::UPPER}: The case will be uppercased.
     */
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
