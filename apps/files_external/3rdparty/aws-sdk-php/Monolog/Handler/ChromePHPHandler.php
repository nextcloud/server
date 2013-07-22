<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Handler;

use Monolog\Formatter\ChromePHPFormatter;

/**
 * Handler sending logs to the ChromePHP extension (http://www.chromephp.com/)
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class ChromePHPHandler extends AbstractProcessingHandler
{
    /**
     * Version of the extension
     */
    const VERSION = '3.0';

    /**
     * Header name
     */
    const HEADER_NAME = 'X-ChromePhp-Data';

    protected static $initialized = false;

    protected static $json = array(
        'version' => self::VERSION,
        'columns' => array('label', 'log', 'backtrace', 'type'),
        'rows' => array(),
    );

    protected static $sendHeaders = true;

    /**
     * {@inheritdoc}
     */
    public function handleBatch(array $records)
    {
        $messages = array();

        foreach ($records as $record) {
            if ($record['level'] < $this->level) {
                continue;
            }
            $messages[] = $this->processRecord($record);
        }

        if (!empty($messages)) {
            $messages = $this->getFormatter()->formatBatch($messages);
            self::$json['rows'] = array_merge(self::$json['rows'], $messages);
            $this->send();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter()
    {
        return new ChromePHPFormatter();
    }

    /**
     * Creates & sends header for a record
     *
     * @see sendHeader()
     * @see send()
     * @param array $record
     */
    protected function write(array $record)
    {
        self::$json['rows'][] = $record['formatted'];

        $this->send();
    }

    /**
     * Sends the log header
     *
     * @see sendHeader()
     */
    protected function send()
    {
        if (!self::$initialized) {
            self::$sendHeaders = $this->headersAccepted();
            self::$json['request_uri'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

            self::$initialized = true;
        }

        $json = @json_encode(self::$json);
        $this->sendHeader(self::HEADER_NAME, base64_encode(utf8_encode($json)));
    }

    /**
     * Send header string to the client
     *
     * @param string $header
     * @param string $content
     */
    protected function sendHeader($header, $content)
    {
        if (!headers_sent() && self::$sendHeaders) {
            header(sprintf('%s: %s', $header, $content));
        }
    }

    /**
     * Verifies if the headers are accepted by the current user agent
     *
     * @return Boolean
     */
    protected function headersAccepted()
    {
        return !isset($_SERVER['HTTP_USER_AGENT'])
               || preg_match('{\bChrome/\d+[\.\d+]*\b}', $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * BC getter for the sendHeaders property that has been made static
     */
    public function __get($property)
    {
        if ('sendHeaders' !== $property) {
            throw new \InvalidArgumentException('Undefined property '.$property);
        }

        return static::$sendHeaders;
    }

    /**
     * BC setter for the sendHeaders property that has been made static
     */
    public function __set($property, $value)
    {
        if ('sendHeaders' !== $property) {
            throw new \InvalidArgumentException('Undefined property '.$property);
        }

        static::$sendHeaders = $value;
    }
}
