<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Logs to an Array backend.
 *
 * @author Chris Corbyn
 */
class Swift_Plugins_Loggers_ArrayLogger implements Swift_Plugins_Logger
{
    /**
     * The log contents.
     *
     * @var array
     */
    private $log = [];

    /**
     * Max size of the log.
     *
     * @var int
     */
    private $size = 0;

    /**
     * Create a new ArrayLogger with a maximum of $size entries.
     *
     * @var int
     */
    public function __construct($size = 50)
    {
        $this->size = $size;
    }

    /**
     * Add a log entry.
     *
     * @param string $entry
     */
    public function add($entry)
    {
        $this->log[] = $entry;
        while (\count($this->log) > $this->size) {
            array_shift($this->log);
        }
    }

    /**
     * Clear the log contents.
     */
    public function clear()
    {
        $this->log = [];
    }

    /**
     * Get this log as a string.
     *
     * @return string
     */
    public function dump()
    {
        return implode(PHP_EOL, $this->log);
    }
}
