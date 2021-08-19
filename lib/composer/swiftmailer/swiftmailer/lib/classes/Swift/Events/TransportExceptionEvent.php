<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Generated when a TransportException is thrown from the Transport system.
 *
 * @author Chris Corbyn
 */
class Swift_Events_TransportExceptionEvent extends Swift_Events_EventObject
{
    /**
     * The Exception thrown.
     *
     * @var Swift_TransportException
     */
    private $exception;

    /**
     * Create a new TransportExceptionEvent for $transport.
     */
    public function __construct(Swift_Transport $transport, Swift_TransportException $ex)
    {
        parent::__construct($transport);
        $this->exception = $ex;
    }

    /**
     * Get the TransportException thrown.
     *
     * @return Swift_TransportException
     */
    public function getException()
    {
        return $this->exception;
    }
}
