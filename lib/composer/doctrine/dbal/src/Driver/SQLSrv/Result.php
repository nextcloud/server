<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\SQLSrv;

use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Result as ResultInterface;

use function sqlsrv_fetch;
use function sqlsrv_fetch_array;
use function sqlsrv_num_fields;
use function sqlsrv_rows_affected;

use const SQLSRV_FETCH_ASSOC;
use const SQLSRV_FETCH_NUMERIC;

final class Result implements ResultInterface
{
    /** @var resource */
    private $statement;

    /**
     * @internal The result can be only instantiated by its driver connection or statement.
     *
     * @param resource $stmt
     */
    public function __construct($stmt)
    {
        $this->statement = $stmt;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchNumeric()
    {
        return $this->fetch(SQLSRV_FETCH_NUMERIC);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAssociative()
    {
        return $this->fetch(SQLSRV_FETCH_ASSOC);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchOne()
    {
        return FetchUtils::fetchOne($this);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllNumeric(): array
    {
        return FetchUtils::fetchAllNumeric($this);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllAssociative(): array
    {
        return FetchUtils::fetchAllAssociative($this);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirstColumn(): array
    {
        return FetchUtils::fetchFirstColumn($this);
    }

    public function rowCount(): int
    {
        if ($this->statement === null) {
            return 0;
        }

        $count = sqlsrv_rows_affected($this->statement);

        if ($count !== false) {
            return $count;
        }

        return 0;
    }

    public function columnCount(): int
    {
        if ($this->statement === null) {
            return 0;
        }

        $count = sqlsrv_num_fields($this->statement);

        if ($count !== false) {
            return $count;
        }

        return 0;
    }

    public function free(): void
    {
        // emulate it by fetching and discarding rows, similarly to what PDO does in this case
        // @link http://php.net/manual/en/pdostatement.closecursor.php
        // @link https://github.com/php/php-src/blob/php-7.0.11/ext/pdo/pdo_stmt.c#L2075
        // deliberately do not consider multiple result sets, since doctrine/dbal doesn't support them
        while (sqlsrv_fetch($this->statement)) {
        }
    }

    /**
     * @return mixed|false
     */
    private function fetch(int $fetchType)
    {
        return sqlsrv_fetch_array($this->statement, $fetchType) ?? false;
    }
}
