<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A generic IoBuffer implementation supporting remote sockets and local processes.
 *
 * @author     Chris Corbyn
 */
class Swift_Transport_StreamBuffer extends Swift_ByteStream_AbstractFilterableInputStream implements Swift_Transport_IoBuffer
{
    /** A primary socket */
    private $stream;

    /** The input stream */
    private $in;

    /** The output stream */
    private $out;

    /** Buffer initialization parameters */
    private $params = [];

    /** The ReplacementFilterFactory */
    private $replacementFactory;

    /** Translations performed on data being streamed into the buffer */
    private $translations = [];

    /**
     * Create a new StreamBuffer using $replacementFactory for transformations.
     */
    public function __construct(Swift_ReplacementFilterFactory $replacementFactory)
    {
        $this->replacementFactory = $replacementFactory;
    }

    /**
     * Perform any initialization needed, using the given $params.
     *
     * Parameters will vary depending upon the type of IoBuffer used.
     */
    public function initialize(array $params)
    {
        $this->params = $params;
        switch ($params['type']) {
            case self::TYPE_PROCESS:
                $this->establishProcessConnection();
                break;
            case self::TYPE_SOCKET:
            default:
                $this->establishSocketConnection();
                break;
        }
    }

    /**
     * Set an individual param on the buffer (e.g. switching to SSL).
     *
     * @param string $param
     * @param mixed  $value
     */
    public function setParam($param, $value)
    {
        if (isset($this->stream)) {
            switch ($param) {
                case 'timeout':
                    if ($this->stream) {
                        stream_set_timeout($this->stream, $value);
                    }
                    break;

                case 'blocking':
                    if ($this->stream) {
                        stream_set_blocking($this->stream, 1);
                    }
            }
        }
        $this->params[$param] = $value;
    }

    public function startTLS()
    {
        // STREAM_CRYPTO_METHOD_TLS_CLIENT only allow tls1.0 connections (some php versions)
        // To support modern tls we allow explicit tls1.0, tls1.1, tls1.2
        // Ssl3 and older are not allowed because they are vulnerable
        // @TODO make tls arguments configurable
        return stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
    }

    /**
     * Perform any shutdown logic needed.
     */
    public function terminate()
    {
        if (isset($this->stream)) {
            switch ($this->params['type']) {
                case self::TYPE_PROCESS:
                    fclose($this->in);
                    fclose($this->out);
                    proc_close($this->stream);
                    break;
                case self::TYPE_SOCKET:
                default:
                    fclose($this->stream);
                    break;
            }
        }
        $this->stream = null;
        $this->out = null;
        $this->in = null;
    }

    /**
     * Set an array of string replacements which should be made on data written
     * to the buffer.
     *
     * This could replace LF with CRLF for example.
     *
     * @param string[] $replacements
     */
    public function setWriteTranslations(array $replacements)
    {
        foreach ($this->translations as $search => $replace) {
            if (!isset($replacements[$search])) {
                $this->removeFilter($search);
                unset($this->translations[$search]);
            }
        }

        foreach ($replacements as $search => $replace) {
            if (!isset($this->translations[$search])) {
                $this->addFilter(
                    $this->replacementFactory->createFilter($search, $replace), $search
                    );
                $this->translations[$search] = true;
            }
        }
    }

    /**
     * Get a line of output (including any CRLF).
     *
     * The $sequence number comes from any writes and may or may not be used
     * depending upon the implementation.
     *
     * @param int $sequence of last write to scan from
     *
     * @return string
     *
     * @throws Swift_IoException
     */
    public function readLine($sequence)
    {
        if (isset($this->out) && !feof($this->out)) {
            $line = fgets($this->out);
            if (0 == \strlen($line)) {
                $metas = stream_get_meta_data($this->out);
                if ($metas['timed_out']) {
                    throw new Swift_IoException('Connection to '.$this->getReadConnectionDescription().' Timed Out');
                }
            }

            return $line;
        }
    }

    /**
     * Reads $length bytes from the stream into a string and moves the pointer
     * through the stream by $length.
     *
     * If less bytes exist than are requested the remaining bytes are given instead.
     * If no bytes are remaining at all, boolean false is returned.
     *
     * @param int $length
     *
     * @return string|bool
     *
     * @throws Swift_IoException
     */
    public function read($length)
    {
        if (isset($this->out) && !feof($this->out)) {
            $ret = fread($this->out, $length);
            if (0 == \strlen($ret)) {
                $metas = stream_get_meta_data($this->out);
                if ($metas['timed_out']) {
                    throw new Swift_IoException('Connection to '.$this->getReadConnectionDescription().' Timed Out');
                }
            }

            return $ret;
        }
    }

    /** Not implemented */
    public function setReadPointer($byteOffset)
    {
    }

    /** Flush the stream contents */
    protected function flush()
    {
        if (isset($this->in)) {
            fflush($this->in);
        }
    }

    /** Write this bytes to the stream */
    protected function doCommit($bytes)
    {
        if (isset($this->in)) {
            $bytesToWrite = \strlen($bytes);
            $totalBytesWritten = 0;

            while ($totalBytesWritten < $bytesToWrite) {
                $bytesWritten = fwrite($this->in, substr($bytes, $totalBytesWritten));
                if (false === $bytesWritten || 0 === $bytesWritten) {
                    break;
                }

                $totalBytesWritten += $bytesWritten;
            }

            if ($totalBytesWritten > 0) {
                return ++$this->sequence;
            }
        }
    }

    /**
     * Establishes a connection to a remote server.
     */
    private function establishSocketConnection()
    {
        $host = $this->params['host'];
        if (!empty($this->params['protocol'])) {
            $host = $this->params['protocol'].'://'.$host;
        }
        $timeout = 15;
        if (!empty($this->params['timeout'])) {
            $timeout = $this->params['timeout'];
        }
        $options = [];
        if (!empty($this->params['sourceIp'])) {
            $options['socket']['bindto'] = $this->params['sourceIp'].':0';
        }

        if (isset($this->params['stream_context_options'])) {
            $options = array_merge($options, $this->params['stream_context_options']);
        }
        $streamContext = stream_context_create($options);

        set_error_handler(function ($type, $msg) {
            throw new Swift_TransportException('Connection could not be established with host '.$this->params['host'].' :'.$msg);
        });
        try {
            $this->stream = stream_socket_client($host.':'.$this->params['port'], $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $streamContext);
        } finally {
            restore_error_handler();
        }

        if (!empty($this->params['blocking'])) {
            stream_set_blocking($this->stream, 1);
        } else {
            stream_set_blocking($this->stream, 0);
        }
        stream_set_timeout($this->stream, $timeout);
        $this->in = &$this->stream;
        $this->out = &$this->stream;
    }

    /**
     * Opens a process for input/output.
     */
    private function establishProcessConnection()
    {
        $command = $this->params['command'];
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
            ];
        $pipes = [];
        $this->stream = proc_open($command, $descriptorSpec, $pipes);
        stream_set_blocking($pipes[2], 0);
        if ($err = stream_get_contents($pipes[2])) {
            throw new Swift_TransportException('Process could not be started ['.$err.']');
        }
        $this->in = &$pipes[0];
        $this->out = &$pipes[1];
    }

    private function getReadConnectionDescription()
    {
        switch ($this->params['type']) {
            case self::TYPE_PROCESS:
                return 'Process '.$this->params['command'];
                break;

            case self::TYPE_SOCKET:
            default:
                $host = $this->params['host'];
                if (!empty($this->params['protocol'])) {
                    $host = $this->params['protocol'].'://'.$host;
                }
                $host .= ':'.$this->params['port'];

                return $host;
                break;
        }
    }
}
