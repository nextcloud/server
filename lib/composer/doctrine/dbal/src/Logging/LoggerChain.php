<?php

namespace Doctrine\DBAL\Logging;

/**
 * Chains multiple SQLLogger.
 */
class LoggerChain implements SQLLogger
{
    /** @var iterable<SQLLogger> */
    private $loggers = [];

    /**
     * @param iterable<SQLLogger> $loggers
     */
    public function __construct(iterable $loggers = [])
    {
        $this->loggers = $loggers;
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        foreach ($this->loggers as $logger) {
            $logger->startQuery($sql, $params, $types);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        foreach ($this->loggers as $logger) {
            $logger->stopQuery();
        }
    }
}
