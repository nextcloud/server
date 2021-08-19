<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Does real time logging of Transport level information.
 *
 * @author     Chris Corbyn
 */
class Swift_Plugins_LoggerPlugin implements Swift_Events_CommandListener, Swift_Events_ResponseListener, Swift_Events_TransportChangeListener, Swift_Events_TransportExceptionListener, Swift_Plugins_Logger
{
    /** The logger which is delegated to */
    private $logger;

    /**
     * Create a new LoggerPlugin using $logger.
     */
    public function __construct(Swift_Plugins_Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Add a log entry.
     *
     * @param string $entry
     */
    public function add($entry)
    {
        $this->logger->add($entry);
    }

    /**
     * Clear the log contents.
     */
    public function clear()
    {
        $this->logger->clear();
    }

    /**
     * Get this log as a string.
     *
     * @return string
     */
    public function dump()
    {
        return $this->logger->dump();
    }

    /**
     * Invoked immediately following a command being sent.
     */
    public function commandSent(Swift_Events_CommandEvent $evt)
    {
        $command = $evt->getCommand();
        $this->logger->add(sprintf('>> %s', $command));
    }

    /**
     * Invoked immediately following a response coming back.
     */
    public function responseReceived(Swift_Events_ResponseEvent $evt)
    {
        $response = $evt->getResponse();
        $this->logger->add(sprintf('<< %s', $response));
    }

    /**
     * Invoked just before a Transport is started.
     */
    public function beforeTransportStarted(Swift_Events_TransportChangeEvent $evt)
    {
        $transportName = \get_class($evt->getSource());
        $this->logger->add(sprintf('++ Starting %s', $transportName));
    }

    /**
     * Invoked immediately after the Transport is started.
     */
    public function transportStarted(Swift_Events_TransportChangeEvent $evt)
    {
        $transportName = \get_class($evt->getSource());
        $this->logger->add(sprintf('++ %s started', $transportName));
    }

    /**
     * Invoked just before a Transport is stopped.
     */
    public function beforeTransportStopped(Swift_Events_TransportChangeEvent $evt)
    {
        $transportName = \get_class($evt->getSource());
        $this->logger->add(sprintf('++ Stopping %s', $transportName));
    }

    /**
     * Invoked immediately after the Transport is stopped.
     */
    public function transportStopped(Swift_Events_TransportChangeEvent $evt)
    {
        $transportName = \get_class($evt->getSource());
        $this->logger->add(sprintf('++ %s stopped', $transportName));
    }

    /**
     * Invoked as a TransportException is thrown in the Transport system.
     */
    public function exceptionThrown(Swift_Events_TransportExceptionEvent $evt)
    {
        $e = $evt->getException();
        $message = $e->getMessage();
        $code = $e->getCode();
        $this->logger->add(sprintf('!! %s (code: %s)', $message, $code));
        $message .= PHP_EOL;
        $message .= 'Log data:'.PHP_EOL;
        $message .= $this->logger->dump();
        $evt->cancelBubble();
        throw new Swift_TransportException($message, $code, $e->getPrevious());
    }
}
