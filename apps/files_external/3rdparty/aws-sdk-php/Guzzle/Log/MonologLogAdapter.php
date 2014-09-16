<?php

namespace Guzzle\Log;

use Monolog\Logger;

/**
 * @deprecated
 * @codeCoverageIgnore
 */
class MonologLogAdapter extends AbstractLogAdapter
{
    /**
     * syslog to Monolog mappings
     */
    private static $mapping = array(
        LOG_DEBUG   => Logger::DEBUG,
        LOG_INFO    => Logger::INFO,
        LOG_WARNING => Logger::WARNING,
        LOG_ERR     => Logger::ERROR,
        LOG_CRIT    => Logger::CRITICAL,
        LOG_ALERT   => Logger::ALERT
    );

    public function __construct(Logger $logObject)
    {
        $this->log = $logObject;
    }

    public function log($message, $priority = LOG_INFO, $extras = array())
    {
        $this->log->addRecord(self::$mapping[$priority], $message, $extras);
    }
}
