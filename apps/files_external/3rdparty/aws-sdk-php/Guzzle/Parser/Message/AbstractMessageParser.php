<?php

namespace Guzzle\Parser\Message;

/**
 * Implements shared message parsing functionality
 */
abstract class AbstractMessageParser implements MessageParserInterface
{
    /**
     * Create URL parts from HTTP message parts
     *
     * @param string $requestUrl Associated URL
     * @param array  $parts      HTTP message parts
     *
     * @return array
     */
    protected function getUrlPartsFromMessage($requestUrl, array $parts)
    {
        // Parse the URL information from the message
        $urlParts = array(
            'path'   => $requestUrl,
            'scheme' => 'http'
        );

        // Check for the Host header
        if (isset($parts['headers']['Host'])) {
            $urlParts['host'] = $parts['headers']['Host'];
        } elseif (isset($parts['headers']['host'])) {
            $urlParts['host'] = $parts['headers']['host'];
        } else {
            $urlParts['host'] = null;
        }

        if (false === strpos($urlParts['host'], ':')) {
            $urlParts['port'] = '';
        } else {
            $hostParts = explode(':', $urlParts['host']);
            $urlParts['host'] = trim($hostParts[0]);
            $urlParts['port'] = (int) trim($hostParts[1]);
            if ($urlParts['port'] == 443) {
                $urlParts['scheme'] = 'https';
            }
        }

        // Check if a query is present
        $path = $urlParts['path'];
        $qpos = strpos($path, '?');
        if ($qpos) {
            $urlParts['query'] = substr($path, $qpos + 1);
            $urlParts['path'] = substr($path, 0, $qpos);
        } else {
            $urlParts['query'] = '';
        }

        return $urlParts;
    }
}
