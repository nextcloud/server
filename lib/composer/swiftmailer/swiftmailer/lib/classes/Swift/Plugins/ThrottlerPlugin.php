<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Throttles the rate at which emails are sent.
 *
 * @author Chris Corbyn
 */
class Swift_Plugins_ThrottlerPlugin extends Swift_Plugins_BandwidthMonitorPlugin implements Swift_Plugins_Sleeper, Swift_Plugins_Timer
{
    /** Flag for throttling in bytes per minute */
    const BYTES_PER_MINUTE = 0x01;

    /** Flag for throttling in emails per second (Amazon SES) */
    const MESSAGES_PER_SECOND = 0x11;

    /** Flag for throttling in emails per minute */
    const MESSAGES_PER_MINUTE = 0x10;

    /**
     * The Sleeper instance for sleeping.
     *
     * @var Swift_Plugins_Sleeper
     */
    private $sleeper;

    /**
     * The Timer instance which provides the timestamp.
     *
     * @var Swift_Plugins_Timer
     */
    private $timer;

    /**
     * The time at which the first email was sent.
     *
     * @var int
     */
    private $start;

    /**
     * The rate at which messages should be sent.
     *
     * @var int
     */
    private $rate;

    /**
     * The mode for throttling.
     *
     * This is {@link BYTES_PER_MINUTE} or {@link MESSAGES_PER_MINUTE}
     *
     * @var int
     */
    private $mode;

    /**
     * An internal counter of the number of messages sent.
     *
     * @var int
     */
    private $messages = 0;

    /**
     * Create a new ThrottlerPlugin.
     *
     * @param int                   $rate
     * @param int                   $mode    defaults to {@link BYTES_PER_MINUTE}
     * @param Swift_Plugins_Sleeper $sleeper (only needed in testing)
     * @param Swift_Plugins_Timer   $timer   (only needed in testing)
     */
    public function __construct($rate, $mode = self::BYTES_PER_MINUTE, Swift_Plugins_Sleeper $sleeper = null, Swift_Plugins_Timer $timer = null)
    {
        $this->rate = $rate;
        $this->mode = $mode;
        $this->sleeper = $sleeper;
        $this->timer = $timer;
    }

    /**
     * Invoked immediately before the Message is sent.
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt)
    {
        $time = $this->getTimestamp();
        if (!isset($this->start)) {
            $this->start = $time;
        }
        $duration = $time - $this->start;

        switch ($this->mode) {
            case self::BYTES_PER_MINUTE:
                $sleep = $this->throttleBytesPerMinute($duration);
                break;
            case self::MESSAGES_PER_SECOND:
                $sleep = $this->throttleMessagesPerSecond($duration);
                break;
            case self::MESSAGES_PER_MINUTE:
                $sleep = $this->throttleMessagesPerMinute($duration);
                break;
            default:
                $sleep = 0;
                break;
        }

        if ($sleep > 0) {
            $this->sleep($sleep);
        }
    }

    /**
     * Invoked when a Message is sent.
     */
    public function sendPerformed(Swift_Events_SendEvent $evt)
    {
        parent::sendPerformed($evt);
        ++$this->messages;
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

    /**
     * Get the current UNIX timestamp.
     *
     * @return int
     */
    public function getTimestamp()
    {
        if (isset($this->timer)) {
            return $this->timer->getTimestamp();
        }

        return time();
    }

    /**
     * Get a number of seconds to sleep for.
     *
     * @param int $timePassed
     *
     * @return int
     */
    private function throttleBytesPerMinute($timePassed)
    {
        $expectedDuration = $this->getBytesOut() / ($this->rate / 60);

        return (int) ceil($expectedDuration - $timePassed);
    }

    /**
     * Get a number of seconds to sleep for.
     *
     * @param int $timePassed
     *
     * @return int
     */
    private function throttleMessagesPerSecond($timePassed)
    {
        $expectedDuration = $this->messages / $this->rate;

        return (int) ceil($expectedDuration - $timePassed);
    }

    /**
     * Get a number of seconds to sleep for.
     *
     * @param int $timePassed
     *
     * @return int
     */
    private function throttleMessagesPerMinute($timePassed)
    {
        $expectedDuration = $this->messages / ($this->rate / 60);

        return (int) ceil($expectedDuration - $timePassed);
    }
}
