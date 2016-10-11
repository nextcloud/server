<?php

namespace Guzzle\Parser\Url;

/**
 * URL parser interface
 */
interface UrlParserInterface
{
    /**
     * Parse a URL using special handling for a subset of UTF-8 characters in the query string if needed.
     *
     * @param string $url URL to parse
     *
     * @return array Returns an array identical to what is returned from parse_url().  When an array key is missing from
     *               this array, you must fill it in with NULL to avoid warnings in calling code.
     */
    public function parseUrl($url);
}
