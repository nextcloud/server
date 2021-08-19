<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Sends Messages over SMTP with ESMTP support.
 *
 * @author Chris Corbyn
 */
class Swift_Transport_EsmtpTransport extends Swift_Transport_AbstractSmtpTransport implements Swift_Transport_SmtpAgent
{
    /**
     * ESMTP extension handlers.
     *
     * @var Swift_Transport_EsmtpHandler[]
     */
    private $handlers = [];

    /**
     * ESMTP capabilities.
     *
     * @var string[]
     */
    private $capabilities = [];

    /**
     * Connection buffer parameters.
     *
     * @var array
     */
    private $params = [
        'protocol' => 'tcp',
        'host' => 'localhost',
        'port' => 25,
        'timeout' => 30,
        'blocking' => 1,
        'tls' => false,
        'type' => Swift_Transport_IoBuffer::TYPE_SOCKET,
        'stream_context_options' => [],
        ];

    /**
     * Creates a new EsmtpTransport using the given I/O buffer.
     *
     * @param Swift_Transport_EsmtpHandler[] $extensionHandlers
     * @param string                         $localDomain
     */
    public function __construct(Swift_Transport_IoBuffer $buf, array $extensionHandlers, Swift_Events_EventDispatcher $dispatcher, $localDomain = '127.0.0.1', Swift_AddressEncoder $addressEncoder = null)
    {
        parent::__construct($buf, $dispatcher, $localDomain, $addressEncoder);
        $this->setExtensionHandlers($extensionHandlers);
    }

    /**
     * Set the host to connect to.
     *
     * Literal IPv6 addresses should be wrapped in square brackets.
     *
     * @param string $host
     *
     * @return $this
     */
    public function setHost($host)
    {
        $this->params['host'] = $host;

        return $this;
    }

    /**
     * Get the host to connect to.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->params['host'];
    }

    /**
     * Set the port to connect to.
     *
     * @param int $port
     *
     * @return $this
     */
    public function setPort($port)
    {
        $this->params['port'] = (int) $port;

        return $this;
    }

    /**
     * Get the port to connect to.
     *
     * @return int
     */
    public function getPort()
    {
        return $this->params['port'];
    }

    /**
     * Set the connection timeout.
     *
     * @param int $timeout seconds
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->params['timeout'] = (int) $timeout;
        $this->buffer->setParam('timeout', (int) $timeout);

        return $this;
    }

    /**
     * Get the connection timeout.
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->params['timeout'];
    }

    /**
     * Set the encryption type (tls or ssl).
     *
     * @param string $encryption
     *
     * @return $this
     */
    public function setEncryption($encryption)
    {
        $encryption = strtolower($encryption);
        if ('tls' == $encryption) {
            $this->params['protocol'] = 'tcp';
            $this->params['tls'] = true;
        } else {
            $this->params['protocol'] = $encryption;
            $this->params['tls'] = false;
        }

        return $this;
    }

    /**
     * Get the encryption type.
     *
     * @return string
     */
    public function getEncryption()
    {
        return $this->params['tls'] ? 'tls' : $this->params['protocol'];
    }

    /**
     * Sets the stream context options.
     *
     * @param array $options
     *
     * @return $this
     */
    public function setStreamOptions($options)
    {
        $this->params['stream_context_options'] = $options;

        return $this;
    }

    /**
     * Returns the stream context options.
     *
     * @return array
     */
    public function getStreamOptions()
    {
        return $this->params['stream_context_options'];
    }

    /**
     * Sets the source IP.
     *
     * IPv6 addresses should be wrapped in square brackets.
     *
     * @param string $source
     *
     * @return $this
     */
    public function setSourceIp($source)
    {
        $this->params['sourceIp'] = $source;

        return $this;
    }

    /**
     * Returns the IP used to connect to the destination.
     *
     * @return string
     */
    public function getSourceIp()
    {
        return $this->params['sourceIp'] ?? null;
    }

    /**
     * Sets whether SMTP pipelining is enabled.
     *
     * By default, support is auto-detected using the PIPELINING SMTP extension.
     * Use this function to override that in the unlikely event of compatibility
     * issues.
     *
     * @param bool $enabled
     *
     * @return $this
     */
    public function setPipelining($enabled)
    {
        $this->pipelining = $enabled;

        return $this;
    }

    /**
     * Returns whether SMTP pipelining is enabled.
     *
     * @return bool|null a boolean if pipelining is explicitly enabled or disabled,
     *                   or null if support is auto-detected
     */
    public function getPipelining()
    {
        return $this->pipelining;
    }

    /**
     * Set ESMTP extension handlers.
     *
     * @param Swift_Transport_EsmtpHandler[] $handlers
     *
     * @return $this
     */
    public function setExtensionHandlers(array $handlers)
    {
        $assoc = [];
        foreach ($handlers as $handler) {
            $assoc[$handler->getHandledKeyword()] = $handler;
        }
        uasort($assoc, function ($a, $b) {
            return $a->getPriorityOver($b->getHandledKeyword());
        });
        $this->handlers = $assoc;
        $this->setHandlerParams();

        return $this;
    }

    /**
     * Get ESMTP extension handlers.
     *
     * @return Swift_Transport_EsmtpHandler[]
     */
    public function getExtensionHandlers()
    {
        return array_values($this->handlers);
    }

    /**
     * Run a command against the buffer, expecting the given response codes.
     *
     * If no response codes are given, the response will not be validated.
     * If codes are given, an exception will be thrown on an invalid response.
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
        $stopSignal = false;
        $response = null;
        foreach ($this->getActiveHandlers() as $handler) {
            $response = $handler->onCommand(
                $this, $command, $codes, $failures, $stopSignal
                );
            if ($stopSignal) {
                return $response;
            }
        }

        return parent::executeCommand($command, $codes, $failures, $pipeline, $address);
    }

    /** Mixin handling method for ESMTP handlers */
    public function __call($method, $args)
    {
        foreach ($this->handlers as $handler) {
            if (\in_array(strtolower($method),
                array_map('strtolower', (array) $handler->exposeMixinMethods())
                )) {
                $return = \call_user_func_array([$handler, $method], $args);
                // Allow fluid method calls
                if (null === $return && 'set' == substr($method, 0, 3)) {
                    return $this;
                } else {
                    return $return;
                }
            }
        }
        trigger_error('Call to undefined method '.$method, E_USER_ERROR);
    }

    /** Get the params to initialize the buffer */
    protected function getBufferParams()
    {
        return $this->params;
    }

    /** Overridden to perform EHLO instead */
    protected function doHeloCommand()
    {
        try {
            $response = $this->executeCommand(
                sprintf("EHLO %s\r\n", $this->domain), [250]
                );
        } catch (Swift_TransportException $e) {
            return parent::doHeloCommand();
        }

        if ($this->params['tls']) {
            try {
                $this->executeCommand("STARTTLS\r\n", [220]);

                if (!$this->buffer->startTLS()) {
                    throw new Swift_TransportException('Unable to connect with TLS encryption');
                }

                try {
                    $response = $this->executeCommand(
                        sprintf("EHLO %s\r\n", $this->domain), [250]
                        );
                } catch (Swift_TransportException $e) {
                    return parent::doHeloCommand();
                }
            } catch (Swift_TransportException $e) {
                $this->throwException($e);
            }
        }

        $this->capabilities = $this->getCapabilities($response);
        if (!isset($this->pipelining)) {
            $this->pipelining = isset($this->capabilities['PIPELINING']);
        }

        $this->setHandlerParams();
        foreach ($this->getActiveHandlers() as $handler) {
            $handler->afterEhlo($this);
        }
    }

    /** Overridden to add Extension support */
    protected function doMailFromCommand($address)
    {
        $address = $this->addressEncoder->encodeString($address);
        $handlers = $this->getActiveHandlers();
        $params = [];
        foreach ($handlers as $handler) {
            $params = array_merge($params, (array) $handler->getMailParams());
        }
        $paramStr = !empty($params) ? ' '.implode(' ', $params) : '';
        $this->executeCommand(
            sprintf("MAIL FROM:<%s>%s\r\n", $address, $paramStr), [250], $failures, true
            );
    }

    /** Overridden to add Extension support */
    protected function doRcptToCommand($address)
    {
        $address = $this->addressEncoder->encodeString($address);
        $handlers = $this->getActiveHandlers();
        $params = [];
        foreach ($handlers as $handler) {
            $params = array_merge($params, (array) $handler->getRcptParams());
        }
        $paramStr = !empty($params) ? ' '.implode(' ', $params) : '';
        $this->executeCommand(
            sprintf("RCPT TO:<%s>%s\r\n", $address, $paramStr), [250, 251, 252], $failures, true, $address
            );
    }

    /** Determine ESMTP capabilities by function group */
    private function getCapabilities($ehloResponse)
    {
        $capabilities = [];
        $ehloResponse = trim($ehloResponse);
        $lines = explode("\r\n", $ehloResponse);
        array_shift($lines);
        foreach ($lines as $line) {
            if (preg_match('/^[0-9]{3}[ -]([A-Z0-9-]+)((?:[ =].*)?)$/Di', $line, $matches)) {
                $keyword = strtoupper($matches[1]);
                $paramStr = strtoupper(ltrim($matches[2], ' ='));
                $params = !empty($paramStr) ? explode(' ', $paramStr) : [];
                $capabilities[$keyword] = $params;
            }
        }

        return $capabilities;
    }

    /** Set parameters which are used by each extension handler */
    private function setHandlerParams()
    {
        foreach ($this->handlers as $keyword => $handler) {
            if (\array_key_exists($keyword, $this->capabilities)) {
                $handler->setKeywordParams($this->capabilities[$keyword]);
            }
        }
    }

    /** Get ESMTP handlers which are currently ok to use */
    private function getActiveHandlers()
    {
        $handlers = [];
        foreach ($this->handlers as $keyword => $handler) {
            if (\array_key_exists($keyword, $this->capabilities)) {
                $handlers[] = $handler;
            }
        }

        return $handlers;
    }
}
