<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Makes sure a connection to a POP3 host has been established prior to connecting to SMTP.
 *
 * @author     Chris Corbyn
 */
class Swift_Plugins_PopBeforeSmtpPlugin implements Swift_Events_TransportChangeListener, Swift_Plugins_Pop_Pop3Connection
{
    /** A delegate connection to use (mostly a test hook) */
    private $connection;

    /** Hostname of the POP3 server */
    private $host;

    /** Port number to connect on */
    private $port;

    /** Encryption type to use (if any) */
    private $crypto;

    /** Username to use (if any) */
    private $username;

    /** Password to use (if any) */
    private $password;

    /** Established connection via TCP socket */
    private $socket;

    /** Connect timeout in seconds */
    private $timeout = 10;

    /** SMTP Transport to bind to */
    private $transport;

    /**
     * Create a new PopBeforeSmtpPlugin for $host and $port.
     *
     * @param string $host   Hostname or IP. Literal IPv6 addresses should be
     *                       wrapped in square brackets.
     * @param int    $port
     * @param string $crypto as "tls" or "ssl"
     */
    public function __construct($host, $port = 110, $crypto = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->crypto = $crypto;
    }

    /**
     * Set a Pop3Connection to delegate to instead of connecting directly.
     *
     * @return $this
     */
    public function setConnection(Swift_Plugins_Pop_Pop3Connection $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Bind this plugin to a specific SMTP transport instance.
     */
    public function bindSmtp(Swift_Transport $smtp)
    {
        $this->transport = $smtp;
    }

    /**
     * Set the connection timeout in seconds (default 10).
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;

        return $this;
    }

    /**
     * Set the username to use when connecting (if needed).
     *
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set the password to use when connecting (if needed).
     *
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Connect to the POP3 host and authenticate.
     *
     * @throws Swift_Plugins_Pop_Pop3Exception if connection fails
     */
    public function connect()
    {
        if (isset($this->connection)) {
            $this->connection->connect();
        } else {
            if (!isset($this->socket)) {
                if (!$socket = fsockopen(
                    $this->getHostString(), $this->port, $errno, $errstr, $this->timeout)) {
                    throw new Swift_Plugins_Pop_Pop3Exception(sprintf('Failed to connect to POP3 host [%s]: %s', $this->host, $errstr));
                }
                $this->socket = $socket;

                if (false === $greeting = fgets($this->socket)) {
                    throw new Swift_Plugins_Pop_Pop3Exception(sprintf('Failed to connect to POP3 host [%s]', trim($greeting)));
                }

                $this->assertOk($greeting);

                if ($this->username) {
                    $this->command(sprintf("USER %s\r\n", $this->username));
                    $this->command(sprintf("PASS %s\r\n", $this->password));
                }
            }
        }
    }

    /**
     * Disconnect from the POP3 host.
     */
    public function disconnect()
    {
        if (isset($this->connection)) {
            $this->connection->disconnect();
        } else {
            $this->command("QUIT\r\n");
            if (!fclose($this->socket)) {
                throw new Swift_Plugins_Pop_Pop3Exception(sprintf('POP3 host [%s] connection could not be stopped', $this->host));
            }
            $this->socket = null;
        }
    }

    /**
     * Invoked just before a Transport is started.
     */
    public function beforeTransportStarted(Swift_Events_TransportChangeEvent $evt)
    {
        if (isset($this->transport)) {
            if ($this->transport !== $evt->getTransport()) {
                return;
            }
        }

        $this->connect();
        $this->disconnect();
    }

    /**
     * Not used.
     */
    public function transportStarted(Swift_Events_TransportChangeEvent $evt)
    {
    }

    /**
     * Not used.
     */
    public function beforeTransportStopped(Swift_Events_TransportChangeEvent $evt)
    {
    }

    /**
     * Not used.
     */
    public function transportStopped(Swift_Events_TransportChangeEvent $evt)
    {
    }

    private function command($command)
    {
        if (!fwrite($this->socket, $command)) {
            throw new Swift_Plugins_Pop_Pop3Exception(sprintf('Failed to write command [%s] to POP3 host', trim($command)));
        }

        if (false === $response = fgets($this->socket)) {
            throw new Swift_Plugins_Pop_Pop3Exception(sprintf('Failed to read from POP3 host after command [%s]', trim($command)));
        }

        $this->assertOk($response);

        return $response;
    }

    private function assertOk($response)
    {
        if ('+OK' != substr($response, 0, 3)) {
            throw new Swift_Plugins_Pop_Pop3Exception(sprintf('POP3 command failed [%s]', trim($response)));
        }
    }

    private function getHostString()
    {
        $host = $this->host;
        switch (strtolower($this->crypto)) {
            case 'ssl':
                $host = 'ssl://'.$host;
                break;

            case 'tls':
                $host = 'tls://'.$host;
                break;
        }

        return $host;
    }
}
