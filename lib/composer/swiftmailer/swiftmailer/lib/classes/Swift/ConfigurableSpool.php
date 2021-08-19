<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2009 Fabien Potencier <fabien.potencier@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Base class for Spools (implements time and message limits).
 *
 * @author Fabien Potencier
 */
abstract class Swift_ConfigurableSpool implements Swift_Spool
{
    /** The maximum number of messages to send per flush */
    private $message_limit;

    /** The time limit per flush */
    private $time_limit;

    /**
     * Sets the maximum number of messages to send per flush.
     *
     * @param int $limit
     */
    public function setMessageLimit($limit)
    {
        $this->message_limit = (int) $limit;
    }

    /**
     * Gets the maximum number of messages to send per flush.
     *
     * @return int The limit
     */
    public function getMessageLimit()
    {
        return $this->message_limit;
    }

    /**
     * Sets the time limit (in seconds) per flush.
     *
     * @param int $limit The limit
     */
    public function setTimeLimit($limit)
    {
        $this->time_limit = (int) $limit;
    }

    /**
     * Gets the time limit (in seconds) per flush.
     *
     * @return int The limit
     */
    public function getTimeLimit()
    {
        return $this->time_limit;
    }
}
