<?php

declare(strict_types=1);

namespace OpenStack\Common\Transport;

use GuzzleHttp\Psr7\Utils as GuzzleUtils;
use GuzzleHttp\UriTemplate\UriTemplate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class Utils
{
    public static function jsonDecode(ResponseInterface $response, bool $assoc = true)
    {
        $jsonErrors = [
            JSON_ERROR_DEPTH          => 'JSON_ERROR_DEPTH - Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'JSON_ERROR_STATE_MISMATCH - Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR      => 'JSON_ERROR_CTRL_CHAR - Unexpected control character found',
            JSON_ERROR_SYNTAX         => 'JSON_ERROR_SYNTAX - Syntax error, malformed JSON',
            JSON_ERROR_UTF8           => 'JSON_ERROR_UTF8 - Malformed UTF-8 characters, possibly incorrectly encoded',
        ];

        $responseBody = (string) $response->getBody();

        if (0 === strlen($responseBody)) {
            return $responseBody;
        }

        $data = json_decode($responseBody, $assoc);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $last = json_last_error();
            throw new \InvalidArgumentException('Unable to parse JSON data: '.(isset($jsonErrors[$last]) ? $jsonErrors[$last] : 'Unknown error'));
        }

        return $data;
    }

    /**
     * Method for flattening a nested array.
     *
     * @param array  $data The nested array
     * @param string $key  The key to extract
     *
     * @return array
     */
    public static function flattenJson($data, string $key = null)
    {
        return (!empty($data) && $key && isset($data[$key])) ? $data[$key] : $data;
    }

    /**
     * Method for normalize an URL string.
     *
     * Append the http:// prefix if not present, and add a
     * closing url separator when missing.
     *
     * @param string $url the url representation
     */
    public static function normalizeUrl(string $url): string
    {
        if (false === strpos($url, 'http')) {
            $url = 'http://'.$url;
        }

        return rtrim($url, '/').'/';
    }

    /**
     * Add an unlimited list of paths to a given URI.
     *
     * @param ...$paths
     */
    public static function addPaths(UriInterface $uri, ...$paths): UriInterface
    {
        return GuzzleUtils::uriFor(rtrim((string) $uri, '/').'/'.implode('/', $paths));
    }

    public static function appendPath(UriInterface $uri, $path): UriInterface
    {
        return GuzzleUtils::uriFor(rtrim((string) $uri, '/').'/'.$path);
    }

    /**
     * Expands a URI template.
     *
     * @param string $template  URI template
     * @param array  $variables Template variables
     */
    public static function uri_template($template, array $variables): string
    {
        if (extension_loaded('uri_template')) {
            // @codeCoverageIgnoreStart
            return \uri_template($template, $variables);
            // @codeCoverageIgnoreEnd
        }

        return UriTemplate::expand($template, $variables);
    }
}
