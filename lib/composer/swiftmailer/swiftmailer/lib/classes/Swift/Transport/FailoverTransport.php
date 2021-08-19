<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Contains a list of redundant Transports so when one fails, the next is used.
 *
 * @author Chris Corbyn
 */
class Swift_Transport_FailoverTransport extends Swift_Transport_LoadBalancedTransport
{
    /**
     * Registered transport currently used.
     *
     * @var Swift_Transport
     */
    private $currentTransport;

    // needed as __construct is called from elsewhere explicitly
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function ping()
    {
        $maxTransports = \count($this->transports);
        for ($i = 0; $i < $maxTransports
            && $transport = $this->getNextTransport(); ++$i) {
            if ($transport->ping()) {
                return true;
            } else {
                $this->killCurrentTransport();
            }
        }

        return \count($this->transports) > 0;
    }

    /**
     * Send the given Message.
     *
     * Recipient/sender data will be retrieved from the Message API.
     * The return value is the number of recipients who were accepted for delivery.
     *
     * @param string[] $failedRecipients An array of failures by-reference
     *
     * @return int
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $maxTransports = \count($this->transports);
        $sent = 0;
        $this->lastUsedTransport = null;

        for ($i = 0; $i < $maxTransports
            && $transport = $this->getNextTransport(); ++$i) {
            try {
                if (!$transport->isStarted()) {
                    $transport->start();
                }

                if ($sent = $transport->send($message, $failedRecipients)) {
                    $this->lastUsedTransport = $transport;

                    return $sent;
                }
            } catch (Swift_TransportException $e) {
                $this->killCurrentTransport();
            }
        }

        if (0 == \count($this->transports)) {
            throw new Swift_TransportException('All Transports in FailoverTransport failed, or no Transports available');
        }

        return $sent;
    }

    protected function getNextTransport()
    {
        if (!isset($this->currentTransport)) {
            $this->currentTransport = parent::getNextTransport();
        }

        return $this->currentTransport;
    }

    protected function killCurrentTransport()
    {
        $this->currentTransport = null;
        parent::killCurrentTransport();
    }
}
