<?php

namespace Guzzle\Parser\Url;

use Guzzle\Common\Version;

/**
 * Parses URLs into parts using PHP's built-in parse_url() function
 * @deprecated Just use parse_url. UTF-8 characters should be percent encoded anyways.
 * @codeCoverageIgnore
 */
class UrlParser implements UrlParserInterface
{
    /** @var bool Whether or not to work with UTF-8 strings */
    protected $utf8 = false;

    /**
     * Set whether or not to attempt to handle UTF-8 strings (still WIP)
     *
     * @param bool $utf8 Set to TRUE to handle UTF string
     */
    public function setUtf8Support($utf8)
    {
        $this->utf8 = $utf8;
    }

    public function parseUrl($url)
    {
        Version::warn(__CLASS__ . ' is deprecated. Just use parse_url()');

        static $defaults = array('scheme' => null, 'host' => null, 'path' => null, 'port' => null, 'query' => null,
            'user' => null, 'pass' => null, 'fragment' => null);

        $parts = parse_url($url);

        // Need to handle query parsing specially for UTF-8 requirements
        if ($this->utf8 && isset($parts['query'])) {
            $queryPos = strpos($url, '?');
            if (isset($parts['fragment'])) {
                $parts['query'] = substr($url, $queryPos + 1, strpos($url, '#') - $queryPos - 1);
            } else {
                $parts['query'] = substr($url, $queryPos + 1);
            }
        }

        return $parts + $defaults;
    }
}
