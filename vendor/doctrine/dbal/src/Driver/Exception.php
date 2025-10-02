<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver;

use Throwable;

interface Exception extends Throwable
{
    /**
     * Returns the SQLSTATE the driver was in at the time the error occurred.
     *
     * Returns null if the driver does not provide a SQLSTATE for the error occurred.
     *
     * @return string|null
     */
    public function getSQLState();
}
