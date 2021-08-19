<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Interface for the EventDispatcher which handles the event dispatching layer.
 *
 * @author Chris Corbyn
 */
interface Swift_Events_EventDispatcher
{
    /**
     * Create a new SendEvent for $source and $message.
     *
     * @return Swift_Events_SendEvent
     */
    public function createSendEvent(Swift_Transport $source, Swift_Mime_SimpleMessage $message);

    /**
     * Create a new CommandEvent for $source and $command.
     *
     * @param string $command      That will be executed
     * @param array  $successCodes That are needed
     *
     * @return Swift_Events_CommandEvent
     */
    public function createCommandEvent(Swift_Transport $source, $command, $successCodes = []);

    /**
     * Create a new ResponseEvent for $source and $response.
     *
     * @param string $response
     * @param bool   $valid    If the response is valid
     *
     * @return Swift_Events_ResponseEvent
     */
    public function createResponseEvent(Swift_Transport $source, $response, $valid);

    /**
     * Create a new TransportChangeEvent for $source.
     *
     * @return Swift_Events_TransportChangeEvent
     */
    public function createTransportChangeEvent(Swift_Transport $source);

    /**
     * Create a new TransportExceptionEvent for $source.
     *
     * @return Swift_Events_TransportExceptionEvent
     */
    public function createTransportExceptionEvent(Swift_Transport $source, Swift_TransportException $ex);

    /**
     * Bind an event listener to this dispatcher.
     */
    public function bindEventListener(Swift_Events_EventListener $listener);

    /**
     * Dispatch the given Event to all suitable listeners.
     *
     * @param string $target method
     */
    public function dispatchEvent(Swift_Events_EventObject $evt, $target);
}
