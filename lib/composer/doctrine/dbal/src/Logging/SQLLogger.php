<?php

namespace Doctrine\DBAL\Logging;

use Doctrine\DBAL\Types\Type;

/**
 * Interface for SQL loggers.
 */
interface SQLLogger
{
    /**
     * Logs a SQL statement somewhere.
     *
     * @param string                                                                    $sql    SQL statement
     * @param list<mixed>|array<string, mixed>|null                                     $params Statement parameters
     * @param array<int, Type|int|string|null>|array<string, Type|int|string|null>|null $types  Parameter types
     *
     * @return void
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null);

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * @return void
     */
    public function stopQuery();
}
