<?php

namespace Guzzle\Parser\Message;

/**
 * Default request and response parser used by Guzzle. Optimized for speed.
 */
class MessageParser extends AbstractMessageParser
{
    public function parseRequest($message)
    {
        if (!$message) {
            return false;
        }

        $parts = $this->parseMessage($message);

        // Parse the protocol and protocol version
        if (isset($parts['start_line'][2])) {
            $startParts = explode('/', $parts['start_line'][2]);
            $protocol = strtoupper($startParts[0]);
            $version = isset($startParts[1]) ? $startParts[1] : '1.1';
        } else {
            $protocol = 'HTTP';
            $version = '1.1';
        }

        $parsed = array(
            'method'   => strtoupper($parts['start_line'][0]),
            'protocol' => $protocol,
            'version'  => $version,
            'headers'  => $parts['headers'],
            'body'     => $parts['body']
        );

        $parsed['request_url'] = $this->getUrlPartsFromMessage($parts['start_line'][1], $parsed);

        return $parsed;
    }

    public function parseResponse($message)
    {
        if (!$message) {
            return false;
        }

        $parts = $this->parseMessage($message);
        list($protocol, $version) = explode('/', trim($parts['start_line'][0]));

        return array(
            'protocol'      => $protocol,
            'version'       => $version,
            'code'          => $parts['start_line'][1],
            'reason_phrase' => isset($parts['start_line'][2]) ? $parts['start_line'][2] : '',
            'headers'       => $parts['headers'],
            'body'          => $parts['body']
        );
    }

    /**
     * Parse a message into parts
     *
     * @param string $message Message to parse
     *
     * @return array
     */
    protected function parseMessage($message)
    {
        $startLine = null;
        $headers = array();
        $body = '';

        // Iterate over each line in the message, accounting for line endings
        $lines = preg_split('/(\\r?\\n)/', $message, -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 0, $totalLines = count($lines); $i < $totalLines; $i += 2) {

            $line = $lines[$i];

            // If two line breaks were encountered, then this is the end of body
            if (empty($line)) {
                if ($i < $totalLines - 1) {
                    $body = implode('', array_slice($lines, $i + 2));
                }
                break;
            }

            // Parse message headers
            if (!$startLine) {
                $startLine = explode(' ', $line, 3);
            } elseif (strpos($line, ':')) {
                $parts = explode(':', $line, 2);
                $key = trim($parts[0]);
                $value = isset($parts[1]) ? trim($parts[1]) : '';
                if (!isset($headers[$key])) {
                    $headers[$key] = $value;
                } elseif (!is_array($headers[$key])) {
                    $headers[$key] = array($headers[$key], $value);
                } else {
                    $headers[$key][] = $value;
                }
            }
        }

        return array(
            'start_line' => $startLine,
            'headers'    => $headers,
            'body'       => $body
        );
    }
}
