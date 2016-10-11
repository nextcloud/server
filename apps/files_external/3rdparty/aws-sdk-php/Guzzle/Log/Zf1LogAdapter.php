<?php

namespace Guzzle\Log;

use Guzzle\Common\Version;

/**
 * Adapts a Zend Framework 1 logger object
 * @deprecated
 * @codeCoverageIgnore
 */
class Zf1LogAdapter extends AbstractLogAdapter
{
    public function __construct(\Zend_Log $logObject)
    {
        $this->log = $logObject;
        Version::warn(__CLASS__ . ' is deprecated');
    }

    public function log($message, $priority = LOG_INFO, $extras = array())
    {
        $this->log->log($message, $priority, $extras);
    }
}
