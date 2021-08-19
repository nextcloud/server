<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Sends Messages over SMTP.
 *
 * @author Chris Corbyn
 */
abstract class Swift_Transport_AbstractSmtpTransport implements Swift_Transport
{
    /** Input-Output buffer for sending/receiving SMTP commands and responses */
    protected $buffer;

    /** Connection status */
    protected $started = false;

    /** The domain name to use in HELO command */
    protected $domain = '[127.0.0.1]';

    /** The event dispatching layer */
    protected $eventDispatcher;

    protected $addressEncoder;

    /** Whether the PIPELINING SMTP extension is enabled (RFC 2920) */
    protected $pipelining = null;

    /** The pipelined commands waiting for response */
    protected $pipeline = [];

    /** Source Ip */
    protected $sourceIp;

    /** Return an array of params for the Buffer */
    abstract protected function getBufferParams();

    /**
     * Creates a new EsmtpTransport using the given I/O buffer.
     *
     * @param string $localDomain
     */
    public function __construct(Swift_Transport_IoBuffer $buf, Swift_Events_EventDispatcher $dispatcher, $localDomain = '127.0.0.1', Swift_AddressEncoder $addressEncoder = null)
    {
        $this->buffer = $buf;
        $this->eventDispatcher = $dispatcher;
        $this->addressEncoder = $addressEncoder ?? new Swift_AddressEncoder_IdnAddressEncoder();
        $this->setLocalDomain($localDomain);
    }

    /**
     * Set the name of the local domain which Swift will identify itself as.
     *
     * This should be a fully-qualified domain name and should be truly the domain
     * you're using.
     *
     * If your server does not have a domain name, use the IP address. This will
     * automatically be wrapped in square brackets as described in RFC 5321,
     * section 4.1.3.
     *
     * @param string $domain
     *
     * @return $this
     */
    public function setLocalDomain($domain)
    {
        if ('[' !== substr($domain, 0, 1)) {
            if (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $domain = '['.$domain.']';
            } elseif (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $domain = '[IPv6:'.$domain.']';
            }
        }

        $this->domain = $domain;

        return $this;
    }

    /**
     * Get the name of the domain Swift will identify as.
     *
     * If an IP address was specified, this will be returned wrapped in square
     * brackets as described in RFC 5321, section 4.1.3.
     *
     * @return string
     */
    public function getLocalDomain()
    {
        return $this->domain;
    }

    /**
     * Sets the source IP.
     *
     * @param string $source
     */
    public function setSourceIp($source)
    {
        $this->sourceIp = $source;
    }

    /**
     * Returns the IP used to connect to the destination.
     *
     * @return string
     */
    public function getSourceIp()
    {
        return $this->sourceIp;
    }

    public function setAddressEncoder(Swift_AddressEncoder $addressEncoder)
    {
        $this->addressEncoder = $addressEncoder;
    }

    public function getAddressEncoder()
    {
        return $this->addressEncoder;
    }

    /**
     * Start the SMTP connection.
     */
    public function start()
    {
        if (!$this->started) {
            if ($evt = $this->eventDispatcher->createTransportChangeEvent($this)) {
                $this->eventDispatcher->dispatchEvent($evt, 'beforeTransportStarted');
                if ($evt->bubbleCancelled()) {
                    return;
                }
            }

            try {
                $this->buffer->initialize($this->getBufferParams());
            } catch (Swift_TransportException $e) {
                $this->throwException($e);
            }
            $this->readGreeting();
            $this->doHeloCommand();

            if ($evt) {
                $this->eventDispatcher->dispatchEvent($evt, 'transportStarted');
            }

            $this->started = true;
        }
    }

    /**
     * Test if an SMTP connection has been established.
     *
     * @return bool
     */
    public function isStarted()
    {
        return $this->started;
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
        if (!$this->isStarted()) {
            $this->start();
        }

        $sent = 0;
        $failedRecipients = (array) $failedRecipients;

        if ($evt = $this->eventDispatcher->createSendEvent($this, $message)) {
            $this->eventDispatcher->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }

        if (!$reversePath = $this->getReversePath($message)) {
            $this->throwException(new Swift_TransportException('Cannot send message without a sender address'));
        }

        $to = (array) $message->getTo();
        $cc = (array) $message->getCc();
        $bcc = (array) $message->getBcc();
        $tos = array_merge($to, $cc, $bcc);

        $message->setBcc([]);

        try {
            $sent += $this->sendTo($message, $reversePath, $tos, $failedRecipients);
        } finally {
            $message->setBcc($bcc);
        }

        if ($evt) {
            if ($sent == \count($to) + \count($cc) + \count($bcc)) {
                $evt->setResult(Swift_Events_SendEvent::RESULT_SUCCESS);
            } elseif ($sent > 0) {
                $evt->setResult(Swift_Events_SendEvent::RESULT_TENTATIVE);
            } else {
                $evt->setResult(Swift_Events_SendEvent::RESULT_FAILED);
            }
            $evt->setFailedRecipients($failedRecipients);
            $this->eventDispatcher->dispatchEvent($evt, 'sendPerformed');
        }

        $message->generateId(); //Make sure a new Message ID is used

        return $sent;
    }

    /**
     * Stop the SMTP connection.
     */
    public function stop()
    {
        if ($this->started) {
            if ($evt = $this->eventDispatcher->createTransportChangeEvent($this)) {
                $this->eventDispatcher->dispatchEvent($evt, 'beforeTransportStopped');
                if ($evt->bubbleCancelled()) {
                    return;
                }
            }

            try {
                $this->executeCommand("QUIT\r\n", [221]);
            } catch (Swift_TransportException $e) {
            }

            try {
                $this->buffer->terminate();

                if ($evt) {
                    $this->eventDispatcher->dispatchEvent($evt, 'transportStopped');
                }
            } catch (Swift_TransportException $e) {
                $this->throwException($e);
            }
        }
        $this->started = false;
    }

    /**
     * {@inheritdoc}
     */
    public function ping()
    {
        try {
            if (!$this->isStarted()) {
                $this->start();
            }

            $this->executeCommand("NOOP\r\n", [250]);
        } catch (Swift_TransportException $e) {
            try {
                $this->stop();
            } catch (Swift_TransportException $e) {
            }

            return false;
        }

        return true;
    }

    /**
     * Register a plugin.
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->eventDispatcher->bindEventListener($plugin);
    }

    /**
     * Reset the current mail transaction.
     */
    public function reset()
    {
        $this->executeCommand("RSET\r\n", [250], $failures, true);
    }

    /**
     * Get the IoBuffer where read/writes are occurring.
     *
     * @return Swift_Transport_IoBuffer
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Run a command against the buffer, expecting the given response codes.
     *
     * If no response codes are given, the response will not be validated.
     * If codes are given, an exception will be thrown on an invalid response.
     * If the command is RCPT TO, and the pipeline is non-empty, no exception
     * will be thrown; instead the failing address is added to $failures.
     *
     * @param string   $command
     * @param int[]    $codes
     * @param string[] $failures An array of failures by-reference
     * @param bool     $pipeline Do not wait for response
     * @param string   $address  the address, if command is RCPT TO
     *
     * @return string|null The server response, or null if pipelining is enabled
     */
    public function executeCommand($command, $codes = [], &$failures = null, $pipeline = false, $address = null)
    {
        $failures = (array) $failures;
        $seq = $this->buffer->write($command);
        if ($evt = $this->eventDispatcher->createCommandEvent($this, $command, $codes)) {
            $this->eventDispatcher->dispatchEvent($evt, 'commandSent');
        }

        $this->pipeline[] = [$command, $seq, $codes, $address];

        if ($pipeline && $this->pipelining) {
            return null;
        }

        $response = null;

        while ($this->pipeline) {
            list($command, $seq, $codes, $address) = array_shift($this->pipeline);
            $response = $this->getFullResponse($seq);
            try {
                $this->assertResponseCode($response, $codes);
            } catch (Swift_TransportException $e) {
                if ($this->pipeline && $address) {
                    $failures[] = $address;
                } else {
                    $this->throwException($e);
                }
            }
        }

        return $response;
    }

    /** Read the opening SMTP greeting */
    protected function readGreeting()
    {
        $this->assertResponseCode($this->getFullResponse(0), [220]);
    }

    /** Send the HELO welcome */
    protected function doHeloCommand()
    {
        $this->executeCommand(
            sprintf("HELO %s\r\n", $this->domain), [250]
            );
    }

    /** Send the MAIL FROM command */
    protected function doMailFromCommand($address)
    {
        $address = $this->addressEncoder->encodeString($address);
        $this->executeCommand(
            sprintf("MAIL FROM:<%s>\r\n", $address), [250], $failures, true
            );
    }

    /** Send the RCPT TO command */
    protected function doRcptToCommand($address)
    {
        $address = $this->addressEncoder->encodeString($address);
        $this->executeCommand(
            sprintf("RCPT TO:<%s>\r\n", $address), [250, 251, 252], $failures, true, $address
            );
    }

    /** Send the DATA command */
    protected function doDataCommand(&$failedRecipients)
    {
        $this->executeCommand("DATA\r\n", [354], $failedRecipients);
    }

    /** Stream the contents of the message over the buffer */
    protected function streamMessage(Swift_Mime_SimpleMessage $message)
    {
        $this->buffer->setWriteTranslations(["\r\n." => "\r\n.."]);
        try {
            $message->toByteStream($this->buffer);
            $this->buffer->flushBuffers();
        } catch (Swift_TransportException $e) {
            $this->throwException($e);
        }
        $this->buffer->setWriteTranslations([]);
        $this->executeCommand("\r\n.\r\n", [250]);
    }

    /** Determine the best-use reverse path for this message */
    protected function getReversePath(Swift_Mime_SimpleMessage $message)
    {
        $return = $message->getReturnPath();
        $sender = $message->getSender();
        $from = $message->getFrom();
        $path = null;
        if (!empty($return)) {
            $path = $return;
        } elseif (!empty($sender)) {
            // Don't use array_keys
            reset($sender); // Reset Pointer to first pos
            $path = key($sender); // Get key
        } elseif (!empty($from)) {
            reset($from); // Reset Pointer to first pos
            $path = key($from); // Get key
        }

        return $path;
    }

    /** Throw a TransportException, first sending it to any listeners */
    protected function throwException(Swift_TransportException $e)
    {
        if ($evt = $this->eventDispatcher->createTransportExceptionEvent($this, $e)) {
            $this->eventDispatcher->dispatchEvent($evt, 'exceptionThrown');
            if (!$evt->bubbleCancelled()) {
                throw $e;
            }
        } else {
            throw $e;
        }
    }

    /** Throws an Exception if a response code is incorrect */
    protected function assertResponseCode($response, $wanted)
    {
        if (!$response) {
            $this->throwException(new Swift_TransportException('Expected response code '.implode('/', $wanted).' but got an empty response'));
        }

        list($code) = sscanf($response, '%3d');
        $valid = (empty($wanted) || \in_array($code, $wanted));

        if ($evt = $this->eventDispatcher->createResponseEvent($this, $response,
            $valid)) {
            $this->eventDispatcher->dispatchEvent($evt, 'responseReceived');
        }

        if (!$valid) {
            $this->throwException(new Swift_TransportException('Expected response code '.implode('/', $wanted).' but got code "'.$code.'", with message "'.$response.'"', $code));
        }
    }

    /** Get an entire multi-line response using its sequence number */
    protected function getFullResponse($seq)
    {
        $response = '';
        try {
            do {
                $line = $this->buffer->readLine($seq);
                $response .= $line;
            } while (null !== $line && false !== $line && ' ' != $line[3]);
        } catch (Swift_TransportException $e) {
            $this->throwException($e);
        } catch (Swift_IoException $e) {
            $this->throwException(new Swift_TransportException($e->getMessage(), 0, $e));
        }

        return $response;
    }

    /** Send an email to the given recipients from the given reverse path */
    private function doMailTransaction($message, $reversePath, array $recipients, array &$failedRecipients)
    {
        $sent = 0;
        $this->doMailFromCommand($reversePath);
        foreach ($recipients as $forwardPath) {
            try {
                $this->doRcptToCommand($forwardPath);
                ++$sent;
            } catch (Swift_TransportException $e) {
                $failedRecipients[] = $forwardPath;
            } catch (Swift_AddressEncoderException $e) {
                $failedRecipients[] = $forwardPath;
            }
        }

        if (0 != $sent) {
            $sent += \count($failedRecipients);
            $this->doDataCommand($failedRecipients);
            $sent -= \count($failedRecipients);

            $this->streamMessage($message);
        } else {
            $this->reset();
        }

        return $sent;
    }

    /** Send a message to the given To: recipients */
    private function sendTo(Swift_Mime_SimpleMessage $message, $reversePath, array $to, array &$failedRecipients)
    {
        if (empty($to)) {
            return 0;
        }

        return $this->doMailTransaction($message, $reversePath, array_keys($to),
            $failedRecipients);
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        try {
            $this->stop();
        } catch (Exception $e) {
        }
    }

    public function __sleep()
    {
        throw new \BadMethodCallException('Cannot serialize '.__CLASS__);
    }

    public function __wakeup()
    {
        throw new \BadMethodCallException('Cannot unserialize '.__CLASS__);
    }
}
