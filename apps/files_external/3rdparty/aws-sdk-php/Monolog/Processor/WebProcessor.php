<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Processor;

/**
 * Injects url/method and remote IP of the current web request in all records
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class WebProcessor
{
    protected $serverData;

    /**
     * @param mixed $serverData array or object w/ ArrayAccess that provides access to the $_SERVER data
     */
    public function __construct($serverData = null)
    {
        if (null === $serverData) {
            $this->serverData =& $_SERVER;
        } elseif (is_array($serverData) || $serverData instanceof \ArrayAccess) {
            $this->serverData = $serverData;
        } else {
            throw new \UnexpectedValueException('$serverData must be an array or object implementing ArrayAccess.');
        }
    }

    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        // skip processing if for some reason request data
        // is not present (CLI or wonky SAPIs)
        if (!isset($this->serverData['REQUEST_URI'])) {
            return $record;
        }

        $record['extra'] = array_merge(
            $record['extra'],
            array(
                'url'         => $this->serverData['REQUEST_URI'],
                'ip'          => isset($this->serverData['REMOTE_ADDR']) ? $this->serverData['REMOTE_ADDR'] : null,
                'http_method' => isset($this->serverData['REQUEST_METHOD']) ? $this->serverData['REQUEST_METHOD'] : null,
                'server'      => isset($this->serverData['SERVER_NAME']) ? $this->serverData['SERVER_NAME'] : null,
                'referrer'    => isset($this->serverData['HTTP_REFERER']) ? $this->serverData['HTTP_REFERER'] : null,
            )
        );

        return $record;
    }
}
