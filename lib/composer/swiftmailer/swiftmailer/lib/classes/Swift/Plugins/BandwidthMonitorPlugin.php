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
class Swift_Plugins_BandwidthMonitorPlugin implements Swift_Events_SendListener, Swift_Events_CommandListener, Swift_Events_ResponseListener, Swift_InputByteStream
{
    /**
     * The outgoing traffic counter.
     *
     * @var int
     */
    private $out = 0;

    /**
     * The incoming traffic counter.
     *
     * @var int
     */
    private $in = 0;

    /** Bound byte streams */
    private $mirrors = [];

    /**
     * Not used.
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt)
    {
    }

    /**
     * Invoked immediately after the Message is sent.
     */
    public function sendPerformed(Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();
        $message->toByteStream($this);
    }

    /**
     * Invoked immediately following a command being sent.
     */
    public function commandSent(Swift_Events_CommandEvent $evt)
    {
        $command = $evt->getCommand();
        $this->out += \strlen($command);
    }

    /**
     * Invoked immediately following a response coming back.
     */
    public function responseReceived(Swift_Events_ResponseEvent $evt)
    {
        $response = $evt->getResponse();
        $this->in += \strlen($response);
    }

    /**
     * Called when a message is sent so that the outgoing counter can be increased.
     *
     * @param string $bytes
     */
    public function write($bytes)
    {
        $this->out += \strlen($bytes);
        foreach ($this->mirrors as $stream) {
            $stream->write($bytes);
        }
    }

    /**
     * Not used.
     */
    public function commit()
    {
    }

    /**
     * Attach $is to this stream.
     *
     * The stream acts as an observer, receiving all data that is written.
     * All {@link write()} and {@link flushBuffers()} operations will be mirrored.
     */
    public function bind(Swift_InputByteStream $is)
    {
        $this->mirrors[] = $is;
    }

    /**
     * Remove an already bound stream.
     *
     * If $is is not bound, no errors will be raised.
     * If the stream currently has any buffered data it will be written to $is
     * before unbinding occurs.
     */
    public function unbind(Swift_InputByteStream $is)
    {
        foreach ($this->mirrors as $k => $stream) {
            if ($is === $stream) {
                unset($this->mirrors[$k]);
            }
        }
    }

    /**
     * Not used.
     */
    public function flushBuffers()
    {
        foreach ($this->mirrors as $stream) {
            $stream->flushBuffers();
        }
    }

    /**
     * Get the total number of bytes sent to the server.
     *
     * @return int
     */
    public function getBytesOut()
    {
        return $this->out;
    }

    /**
     * Get the total number of bytes received from the server.
     *
     * @return int
     */
    public function getBytesIn()
    {
        return $this->in;
    }

    /**
     * Reset the internal counters to zero.
     */
    public function reset()
    {
        $this->out = 0;
        $this->in = 0;
    }
}
