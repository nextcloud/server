<?php

namespace Doctrine\DBAL\Logging;

use Doctrine\Deprecations\Deprecation;

use function microtime;

/**
 * Includes executed SQLs in a Debug Stack.
 *
 * @deprecated
 */
class DebugStack implements SQLLogger
{
    /**
     * Executed SQL queries.
     *
     * @var array<int, array<string, mixed>>
     */
    public $queries = [];

    /**
     * If Debug Stack is enabled (log queries) or not.
     *
     * @var bool
     */
    public $enabled = true;

    /** @var float|null */
    public $start = null;

    /** @var int */
    public $currentQuery = 0;

    public function __construct()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4967',
            'DebugStack is deprecated.',
        );
    }

    /**
     * {@inheritDoc}
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        if (! $this->enabled) {
            return;
        }

        $this->start = microtime(true);

        $this->queries[++$this->currentQuery] = [
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
            'executionMS' => 0,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function stopQuery()
    {
        if (! $this->enabled) {
            return;
        }

        $this->queries[$this->currentQuery]['executionMS'] = microtime(true) - $this->start;
    }
}
