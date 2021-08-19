<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Sends Messages via an abstract Transport subsystem.
 *
 * @author Chris Corbyn
 */
interface Swift_Transport
{
    /**
     * Test if this Transport mechanism has started.
     *
     * @return bool
     */
    public function isStarted();

    /**
     * Start this Transport mechanism.
     */
    public function start();

    /**
     * Stop this Transport mechanism.
     */
    public function stop();

    /**
     * Check if this Transport mechanism is alive.
     *
     * If a Transport mechanism session is no longer functional, the method
     * returns FALSE. It is the responsibility of the developer to handle this
     * case and restart the Transport mechanism manually.
     *
     * @example
     *
     *   if (!$transport->ping()) {
     *      $transport->stop();
     *      $transport->start();
     *   }
     *
     * The Transport mechanism will be started, if it is not already.
     *
     * It is undefined if the Transport mechanism attempts to restart as long as
     * the return value reflects whether the mechanism is now functional.
     *
     * @return bool TRUE if the transport is alive
     */
    public function ping();

    /**
     * Send the given Message.
     *
     * Recipient/sender data will be retrieved from the Message API.
     * The return value is the number of recipients who were accepted for delivery.
     *
     * This is the responsibility of the send method to start the transport if needed.
     *
     * @param string[] $failedRecipients An array of failures by-reference
     *
     * @return int
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null);

    /**
     * Register a plugin in the Transport.
     */
    public function registerPlugin(Swift_Events_EventListener $plugin);
}
