<?php

/*
 * This file is part of the webmozart/path-util package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\PathUtil;

use InvalidArgumentException;
use Webmozart\Assert\Assert;

/**
 * Contains utility methods for handling URL strings.
 *
 * The methods in this class are able to deal with URLs.
 *
 * @since  2.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Claudio Zizza <claudio@budgegeria.de>
 */
final class Url
{
    /**
     * Turns a URL into a relative path.
     *
     * The result is a canonical path. This class is using functionality of Path class.
     *
     * @see Path
     *
     * @param string $url     A URL to make relative.
     * @param string $baseUrl A base URL.
     *
     * @return string
     *
     * @throws InvalidArgumentException If the URL and base URL does
     *                                  not match.
     */
    public static function makeRelative($url, $baseUrl)
    {
        Assert::string($url, 'The URL must be a string. Got: %s');
        Assert::string($baseUrl, 'The base URL must be a string. Got: %s');
        Assert::contains($baseUrl, '://', '%s is not an absolute Url.');

        list($baseHost, $basePath) = self::split($baseUrl);

        if (false === strpos($url, '://')) {
            if (0 === strpos($url, '/')) {
                $host = $baseHost;
            } else {
                $host = '';
            }
            $path = $url;
        } else {
            list($host, $path) = self::split($url);
        }

        if ('' !== $host && $host !== $baseHost) {
            throw new InvalidArgumentException(sprintf(
                'The URL "%s" cannot be made relative to "%s" since their host names are different.',
                $host,
                $baseHost
            ));
        }

        return Path::makeRelative($path, $basePath);
    }

    /**
     * Splits a URL into its host and the path.
     *
     * ```php
     * list ($root, $path) = Path::split("http://example.com/webmozart")
     * // => array("http://example.com", "/webmozart")
     *
     * list ($root, $path) = Path::split("http://example.com")
     * // => array("http://example.com", "")
     * ```
     *
     * @param string $url The URL to split.
     *
     * @return string[] An array with the host and the path of the URL.
     *
     * @throws InvalidArgumentException If $url is not a URL.
     */
    private static function split($url)
    {
        $pos = strpos($url, '://');
        $scheme = substr($url, 0, $pos + 3);
        $url = substr($url, $pos + 3);

        if (false !== ($pos = strpos($url, '/'))) {
            $host = substr($url, 0, $pos);
            $url = substr($url, $pos);
        } else {
            // No path, only host
            $host = $url;
            $url = '/';
        }

        // At this point, we have $scheme, $host and $path
        $root = $scheme.$host;

        return array($root, $url);
    }
}
