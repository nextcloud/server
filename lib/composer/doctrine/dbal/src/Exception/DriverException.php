<?php

namespace Doctrine\DBAL\Exception;

use Doctrine\DBAL\Driver\Exception as TheDriverException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query;

use function assert;

/**
 * Base class for all errors detected in the driver.
 *
 * @psalm-immutable
 */
class DriverException extends Exception implements TheDriverException
{
    /**
     * The query that triggered the exception, if any.
     *
     * @var Query|null
     */
    private $query;

    /**
     * @internal
     *
     * @param TheDriverException $driverException The DBAL driver exception to chain.
     * @param Query|null         $query           The SQL query that triggered the exception, if any.
     */
    public function __construct(TheDriverException $driverException, ?Query $query)
    {
        if ($query !== null) {
            $message = 'An exception occurred while executing a query: ' . $driverException->getMessage();
        } else {
            $message = 'An exception occurred in the driver: ' . $driverException->getMessage();
        }

        parent::__construct($message, $driverException->getCode(), $driverException);

        $this->query = $query;
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLState()
    {
        $previous = $this->getPrevious();
        assert($previous instanceof TheDriverException);

        return $previous->getSQLState();
    }

    public function getQuery(): ?Query
    {
        return $this->query;
    }
}
