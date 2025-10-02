<?php

namespace Doctrine\DBAL\Driver\Middleware;

use Doctrine\DBAL\Driver\Result;

abstract class AbstractResultMiddleware implements Result
{
    private Result $wrappedResult;

    public function __construct(Result $result)
    {
        $this->wrappedResult = $result;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchNumeric()
    {
        return $this->wrappedResult->fetchNumeric();
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAssociative()
    {
        return $this->wrappedResult->fetchAssociative();
    }

    /**
     * {@inheritDoc}
     */
    public function fetchOne()
    {
        return $this->wrappedResult->fetchOne();
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllNumeric(): array
    {
        return $this->wrappedResult->fetchAllNumeric();
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllAssociative(): array
    {
        return $this->wrappedResult->fetchAllAssociative();
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirstColumn(): array
    {
        return $this->wrappedResult->fetchFirstColumn();
    }

    public function rowCount(): int
    {
        return $this->wrappedResult->rowCount();
    }

    public function columnCount(): int
    {
        return $this->wrappedResult->columnCount();
    }

    public function free(): void
    {
        $this->wrappedResult->free();
    }
}
