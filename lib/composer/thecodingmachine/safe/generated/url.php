<?php

namespace Safe;

use Safe\Exceptions\UrlException;

/**
 * Decodes a base64 encoded data.
 *
 * @param string $data The encoded data.
 * @param bool $strict If the strict parameter is set to TRUE
 * then the base64_decode function will return
 * FALSE if the input contains character from outside the base64
 * alphabet. Otherwise invalid characters will be silently discarded.
 * @return string Returns the decoded data. The returned data may be
 * binary.
 * @throws UrlException
 *
 */
function base64_decode(string $data, bool $strict = false): string
{
    error_clear_last();
    $result = \base64_decode($data, $strict);
    if ($result === false) {
        throw UrlException::createFromPhpError();
    }
    return $result;
}


/**
 * get_headers returns an array with the headers sent
 * by the server in response to a HTTP request.
 *
 * @param string $url The target URL.
 * @param int $format If the optional format parameter is set to non-zero,
 * get_headers parses the response and sets the
 * array's keys.
 * @param resource $context A valid context resource created with
 * stream_context_create.
 * @return array Returns an indexed or associative array with the headers.
 * @throws UrlException
 *
 */
function get_headers(string $url, int $format = 0, $context = null): array
{
    error_clear_last();
    if ($context !== null) {
        $result = \get_headers($url, $format, $context);
    } else {
        $result = \get_headers($url, $format);
    }
    if ($result === false) {
        throw UrlException::createFromPhpError();
    }
    return $result;
}


/**
 * This function parses a URL and returns an associative array containing any
 * of the various components of the URL that are present.
 * The values of the array elements are not URL decoded.
 *
 * This function is not meant to validate
 * the given URL, it only breaks it up into the above listed parts. Partial
 * URLs are also accepted, parse_url tries its best to
 * parse them correctly.
 *
 * @param string $url The URL to parse. Invalid characters are replaced by
 * _.
 * @param int $component Specify one of PHP_URL_SCHEME,
 * PHP_URL_HOST, PHP_URL_PORT,
 * PHP_URL_USER, PHP_URL_PASS,
 * PHP_URL_PATH, PHP_URL_QUERY
 * or PHP_URL_FRAGMENT to retrieve just a specific
 * URL component as a string (except when
 * PHP_URL_PORT is given, in which case the return
 * value will be an integer).
 * @return mixed On seriously malformed URLs, parse_url.
 *
 * If the component parameter is omitted, an
 * associative array is returned. At least one element will be
 * present within the array. Potential keys within this array are:
 *
 *
 *
 * scheme - e.g. http
 *
 *
 *
 *
 * host
 *
 *
 *
 *
 * port
 *
 *
 *
 *
 * user
 *
 *
 *
 *
 * pass
 *
 *
 *
 *
 * path
 *
 *
 *
 *
 * query - after the question mark ?
 *
 *
 *
 *
 * fragment - after the hashmark #
 *
 *
 *
 *
 * If the component parameter is specified,
 * parse_url returns a string (or an
 * integer, in the case of PHP_URL_PORT)
 * instead of an array. If the requested component doesn't exist
 * within the given URL, NULL will be returned.
 * @throws UrlException
 *
 */
function parse_url(string $url, int $component = -1)
{
    error_clear_last();
    $result = \parse_url($url, $component);
    if ($result === false) {
        throw UrlException::createFromPhpError();
    }
    return $result;
}
