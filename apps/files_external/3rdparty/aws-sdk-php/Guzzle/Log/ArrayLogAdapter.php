<?php

namespace Guzzle\Log;

/**
 * Stores all log messages in an array
 */
class ArrayLogAdapter implements LogAdapterInterface
{
    protected $logs = array();

    public function log($message, $priority = LOG_INFO, $extras = array())
    {
        $this->logs[] = array('message' => $message, 'priority' => $priority, 'extras' => $extras);
    }

    /**
     * Get logged entries
     *
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Clears logged entries
     */
    public function clearLogs()
    {
        $this->logs = array();
    }
}
