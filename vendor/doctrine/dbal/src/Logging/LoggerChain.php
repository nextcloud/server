<?php

namespace Doctrine\DBAL\Logging;

use Doctrine\Deprecations\Deprecation;

/**
 * Chains multiple SQLLogger.
 *
 * @deprecated
 */
class LoggerChain implements SQLLogger
{
    /** @var iterable<SQLLogger> */
    private iterable $loggers;

    /** @param iterable<SQLLogger> $loggers */
    public function __construct(iterable $loggers = [])
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4967',
            'LoggerChain is deprecated',
        );

        $this->loggers = $loggers;
    }

    /**
     * {@inheritDoc}
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        foreach ($this->loggers as $logger) {
            $logger->startQuery($sql, $params, $types);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function stopQuery()
    {
        foreach ($this->loggers as $logger) {
            $logger->stopQuery();
        }
    }
}
