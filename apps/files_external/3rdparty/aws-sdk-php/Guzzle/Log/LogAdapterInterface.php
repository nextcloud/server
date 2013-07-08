<?php

namespace Guzzle\Log;

/**
 * Adapter class that allows Guzzle to log data to various logging implementations.
 */
interface LogAdapterInterface
{
    /**
     * Log a message at a priority
     *
     * @param string  $message  Message to log
     * @param integer $priority Priority of message (use the \LOG_* constants of 0 - 7)
     * @param array   $extras   Extra information to log in event
     */
    public function log($message, $priority = LOG_INFO, $extras = array());
}
