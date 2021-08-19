<?php

declare(strict_types=1);

namespace Doctrine\DBAL;

use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Driver\Result as DriverResult;
use Doctrine\DBAL\Exception\NoKeyValue;
use LogicException;
use Traversable;

use function array_shift;
use function func_num_args;

class Result
{
    /** @var DriverResult */
    private $result;

    /** @var Connection */
    private $connection;

    /**
     * @internal The result can be only instantiated by {@link Connection} or {@link Statement}.
     */
    public function __construct(DriverResult $result, Connection $connection)
    {
        $this->result     = $result;
        $this->connection = $connection;
    }

    /**
     * Returns the next row of the result as a numeric array or FALSE if there are no more rows.
     *
     * @return list<mixed>|false
     *
     * @throws Exception
     */
    public function fetchNumeric()
    {
        try {
            return $this->result->fetchNumeric();
        } catch (DriverException $e) {
            throw $this->connection->convertException($e);
        }
    }

    /**
     * Returns the next row of the result as an associative array or FALSE if there are no more rows.
     *
     * @return array<string,mixed>|false
     *
     * @throws Exception
     */
    public function fetchAssociative()
    {
        try {
            return $this->result->fetchAssociative();
        } catch (DriverException $e) {
            throw $this->connection->convertException($e);
        }
    }

    /**
     * Returns the first value of the next row of the result or FALSE if there are no more rows.
     *
     * @return mixed|false
     *
     * @throws Exception
     */
    public function fetchOne()
    {
        try {
            return $this->result->fetchOne();
        } catch (DriverException $e) {
            throw $this->connection->convertException($e);
        }
    }

    /**
     * Returns an array containing all of the result rows represented as numeric arrays.
     *
     * @return list<list<mixed>>
     *
     * @throws Exception
     */
    public function fetchAllNumeric(): array
    {
        try {
            return $this->result->fetchAllNumeric();
        } catch (DriverException $e) {
            throw $this->connection->convertException($e);
        }
    }

    /**
     * Returns an array containing all of the result rows represented as associative arrays.
     *
     * @return list<array<string,mixed>>
     *
     * @throws Exception
     */
    public function fetchAllAssociative(): array
    {
        try {
            return $this->result->fetchAllAssociative();
        } catch (DriverException $e) {
            throw $this->connection->convertException($e);
        }
    }

    /**
     * Returns an array containing the values of the first column of the result.
     *
     * @return array<mixed,mixed>
     *
     * @throws Exception
     */
    public function fetchAllKeyValue(): array
    {
        $this->ensureHasKeyValue();

        $data = [];

        foreach ($this->fetchAllNumeric() as [$key, $value]) {
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Returns an associative array with the keys mapped to the first column and the values being
     * an associative array representing the rest of the columns and their values.
     *
     * @return array<mixed,array<string,mixed>>
     *
     * @throws Exception
     */
    public function fetchAllAssociativeIndexed(): array
    {
        $data = [];

        foreach ($this->fetchAllAssociative() as $row) {
            $data[array_shift($row)] = $row;
        }

        return $data;
    }

    /**
     * @return list<mixed>
     *
     * @throws Exception
     */
    public function fetchFirstColumn(): array
    {
        try {
            return $this->result->fetchFirstColumn();
        } catch (DriverException $e) {
            throw $this->connection->convertException($e);
        }
    }

    /**
     * @return Traversable<int,list<mixed>>
     *
     * @throws Exception
     */
    public function iterateNumeric(): Traversable
    {
        try {
            while (($row = $this->result->fetchNumeric()) !== false) {
                yield $row;
            }
        } catch (DriverException $e) {
            throw $this->connection->convertException($e);
        }
    }

    /**
     * @return Traversable<int,array<string,mixed>>
     *
     * @throws Exception
     */
    public function iterateAssociative(): Traversable
    {
        try {
            while (($row = $this->result->fetchAssociative()) !== false) {
                yield $row;
            }
        } catch (DriverException $e) {
            throw $this->connection->convertException($e);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function iterateKeyValue(): Traversable
    {
        $this->ensureHasKeyValue();

        foreach ($this->iterateNumeric() as [$key, $value]) {
            yield $key => $value;
        }
    }

    /**
     * Returns an iterator over the result set with the keys mapped to the first column and the values being
     * an associative array representing the rest of the columns and their values.
     *
     * @return Traversable<mixed,array<string,mixed>>
     *
     * @throws Exception
     */
    public function iterateAssociativeIndexed(): Traversable
    {
        foreach ($this->iterateAssociative() as $row) {
            yield array_shift($row) => $row;
        }
    }

    /**
     * @return Traversable<int,mixed>
     *
     * @throws Exception
     */
    public function iterateColumn(): Traversable
    {
        try {
            while (($value = $this->result->fetchOne()) !== false) {
                yield $value;
            }
        } catch (DriverException $e) {
            throw $this->connection->convertException($e);
        }
    }

    /**
     * @throws Exception
     */
    public function rowCount(): int
    {
        try {
            return $this->result->rowCount();
        } catch (DriverException $e) {
            throw $this->connection->convertException($e);
        }
    }

    /**
     * @throws Exception
     */
    public function columnCount(): int
    {
        try {
            return $this->result->columnCount();
        } catch (DriverException $e) {
            throw $this->connection->convertException($e);
        }
    }

    public function free(): void
    {
        $this->result->free();
    }

    /**
     * @throws Exception
     */
    private function ensureHasKeyValue(): void
    {
        $columnCount = $this->columnCount();

        if ($columnCount < 2) {
            throw NoKeyValue::fromColumnCount($columnCount);
        }
    }

    /**
     * BC layer for a wide-spread use-case of old DBAL APIs
     *
     * @deprecated This API is deprecated and will be removed after 2022
     *
     * @return mixed
     */
    public function fetch(int $mode = FetchMode::ASSOCIATIVE)
    {
        if (func_num_args() > 1) {
            throw new LogicException('Only invocations with one argument are still supported by this legecy API.');
        }

        if ($mode === FetchMode::ASSOCIATIVE) {
            return $this->fetchAssociative();
        }

        if ($mode === FetchMode::NUMERIC) {
            return $this->fetchNumeric();
        }

        if ($mode === FetchMode::COLUMN) {
            return $this->fetchOne();
        }

        throw new LogicException('Only fetch modes declared on Doctrine\DBAL\FetchMode are supported by legacy API.');
    }

    /**
     * BC layer for a wide-spread use-case of old DBAL APIs
     *
     * @deprecated This API is deprecated and will be removed after 2022
     *
     * @return list<mixed>
     */
    public function fetchAll(int $mode = FetchMode::ASSOCIATIVE): array
    {
        if (func_num_args() > 1) {
            throw new LogicException('Only invocations with one argument are still supported by this legecy API.');
        }

        if ($mode === FetchMode::ASSOCIATIVE) {
            return $this->fetchAllAssociative();
        }

        if ($mode === FetchMode::NUMERIC) {
            return $this->fetchAllNumeric();
        }

        if ($mode === FetchMode::COLUMN) {
            return $this->fetchFirstColumn();
        }

        throw new LogicException('Only fetch modes declared on Doctrine\DBAL\FetchMode are supported by legacy API.');
    }
}
