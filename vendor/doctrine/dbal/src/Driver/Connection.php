<?php

namespace Doctrine\DBAL\Driver;

use Doctrine\DBAL\ParameterType;

/**
 * Connection interface.
 * Driver connections must implement this interface.
 *
 * @method resource|object getNativeConnection()
 */
interface Connection
{
    /**
     * Prepares a statement for execution and returns a Statement object.
     *
     * @throws Exception
     */
    public function prepare(string $sql): Statement;

    /**
     * Executes an SQL statement, returning a result set as a Statement object.
     *
     * @throws Exception
     */
    public function query(string $sql): Result;

    /**
     * Quotes a string for use in a query.
     *
     * The usage of this method is discouraged. Use prepared statements
     * or {@see AbstractPlatform::quoteStringLiteral()} instead.
     *
     * @param mixed $value
     * @param int   $type
     *
     * @return mixed
     */
    public function quote($value, $type = ParameterType::STRING);

    /**
     * Executes an SQL statement and return the number of affected rows.
     *
     * @throws Exception
     */
    public function exec(string $sql): int;

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string|null $name
     *
     * @return string|int|false
     *
     * @throws Exception
     */
    public function lastInsertId($name = null);

    /**
     * Initiates a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     *
     * @throws Exception
     */
    public function beginTransaction();

    /**
     * Commits a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     *
     * @throws Exception
     */
    public function commit();

    /**
     * Rolls back the current transaction, as initiated by beginTransaction().
     *
     * @return bool TRUE on success or FALSE on failure.
     *
     * @throws Exception
     */
    public function rollBack();
}
