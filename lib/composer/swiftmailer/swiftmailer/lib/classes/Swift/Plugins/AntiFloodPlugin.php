<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Reduces network flooding when sending large amounts of mail.
 *
 * @author Chris Corbyn
 */
class Swift_Plugins_AntiFloodPlugin implements Swift_Events_SendListener, Swift_Plugins_Sleeper
{
    /**
     * The number of emails to send before restarting Transport.
     *
     * @var int
     */
    private $threshold;

    /**
     * The number of seconds to sleep for during a restart.
     *
     * @var int
     */
    private $sleep;

    /**
     * The internal counter.
     *
     * @var int
     */
    private $counter = 0;

    /**
     * The Sleeper instance for sleeping.
     *
     * @var Swift_Plugins_Sleeper
     */
    private $sleeper;

    /**
     * Create a new AntiFloodPlugin with $threshold and $sleep time.
     *
     * @param int                   $threshold
     * @param int                   $sleep     time
     * @param Swift_Plugins_Sleeper $sleeper   (not needed really)
     */
    public function __construct($threshold = 99, $sleep = 0, Swift_Plugins_Sleeper $sleeper = null)
    {
        $this->setThreshold($threshold);
        $this->setSleepTime($sleep);
        $this->sleeper = $sleeper;
    }

    /**
     * Set the number of emails to send before restarting.
     *
     * @param int $threshold
     */
    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;
    }

    /**
     * Get the number of emails to send before restarting.
     *
     * @return int
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * Set the number of seconds to sleep for during a restart.
     *
     * @param int $sleep time
     */
    public function setSleepTime($sleep)
    {
        $this->sleep = $sleep;
    }

    /**
     * Get the number of seconds to sleep for during a restart.
     *
     * @return int
     */
    public function getSleepTime()
    {
        return $this->sleep;
    }

    /**
     * Invoked immediately before the Message is sent.
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt)
    {
    }

    /**
     * Invoked immediately after the Message is sent.
     */
    public function sendPerformed(Swift_Events_SendEvent $evt)
    {
        ++$this->counter;
        if ($this->counter >= $this->threshold) {
            $transport = $evt->getTransport();
            $transport->stop();
            if ($this->sleep) {
                $this->sleep($this->sleep);
            }
            $transport->start();
            $this->counter = 0;
        }
    }

    /**
     * Sleep for $seconds.
     *
     * @param int $seconds
     */
    public function sleep($seconds)
    {
        if (isset($this->sleeper)) {
            $this->sleeper->sleep($seconds);
        } else {
            sleep($seconds);
        }
    }
}
