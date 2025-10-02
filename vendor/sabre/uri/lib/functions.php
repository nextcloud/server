<?php

declare(strict_types=1);

namespace Sabre\Uri;

/**
 * This file contains all the uri handling functions.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/
 */

/**
 * Resolves relative urls, like a browser would.
 *
 * This function takes a basePath, which itself _may_ also be relative, and
 * then applies the relative path on top of it.
 *
 * @throws InvalidUriException
 */
function resolve(string $basePath, string $newPath): string
{
    $delta = parse($newPath);

    // If the new path defines a scheme, it's absolute and we can just return
    // that.
    if (null !== $delta['scheme']) {
        return build($delta);
    }

    $base = parse($basePath);
    $pick = function ($part) use ($base, $delta) {
        if (null !== $delta[$part]) {
            return $delta[$part];
        } elseif (null !== $base[$part]) {
            return $base[$part];
        }

        return null;
    };

    $newParts = [];

    $newParts['scheme'] = $pick('scheme');
    $newParts['host'] = $pick('host');
    $newParts['port'] = $pick('port');

    if (is_string($delta['path']) and strlen($delta['path']) > 0) {
        // If the path starts with a slash
        if ('/' === $delta['path'][0]) {
            $path = $delta['path'];
        } else {
            // Removing last component from base path.
            $path = (string) $base['path'];
            $length = strrpos($path, '/');
            if (false !== $length) {
                $path = substr($path, 0, $length);
            }
            $path .= '/'.$delta['path'];
        }
    } else {
        $path = $base['path'] ?? '/';
        if ('' === $path) {
            $path = '/';
        }
    }
    // Removing .. and .
    $pathParts = explode('/', $path);
    $newPathParts = [];
    foreach ($pathParts as $pathPart) {
        switch ($pathPart) {
            // case '' :
            case '.':
                break;
            case '..':
                array_pop($newPathParts);
                break;
            default:
                $newPathParts[] = $pathPart;
                break;
        }
    }

    $path = implode('/', $newPathParts);

    // If the source url ended with a /, we want to preserve that.
    $newParts['path'] = 0 === strpos($path, '/') ? $path : '/'.$path;
    // From PHP 8, no "?" query at all causes 'query' to be null.
    // An empty query "http://example.com/foo?" causes 'query' to be the empty string
    if (null !== $delta['query'] && '' !== $delta['query']) {
        $newParts['query'] = $delta['query'];
    } elseif (isset($base['query']) && null === $delta['host'] && null === $delta['path']) {
        // Keep the old query if host and path didn't change
        $newParts['query'] = $base['query'];
    }
    // From PHP 8, no "#" fragment at all causes 'fragment' to be null.
    // An empty fragment "http://example.com/foo#" causes 'fragment' to be the empty string
    if (null !== $delta['fragment'] && '' !== $delta['fragment']) {
        $newParts['fragment'] = $delta['fragment'];
    }

    return build($newParts);
}

/**
 * Takes a URI or partial URI as its argument, and normalizes it.
 *
 * After normalizing a URI, you can safely compare it to other URIs.
 * This function will for instance convert a %7E into a tilde, according to
 * rfc3986.
 *
 * It will also change a %3a into a %3A.
 *
 * @throws InvalidUriException
 */
function normalize(string $uri): string
{
    $parts = parse($uri);

    if (null !== $parts['path']) {
        $pathParts = explode('/', ltrim($parts['path'], '/'));
        $newPathParts = [];
        foreach ($pathParts as $pathPart) {
            switch ($pathPart) {
                case '.':
                    // skip
                    break;
                case '..':
                    // One level up in the hierarchy
                    array_pop($newPathParts);
                    break;
                default:
                    // Ensuring that everything is correctly percent-encoded.
                    $newPathParts[] = rawurlencode(rawurldecode($pathPart));
                    break;
            }
        }
        $parts['path'] = '/'.implode('/', $newPathParts);
    }

    if (null !== $parts['scheme']) {
        $parts['scheme'] = strtolower($parts['scheme']);
        $defaultPorts = [
            'http' => '80',
            'https' => '443',
        ];

        if (null !== $parts['port'] && isset($defaultPorts[$parts['scheme']]) && $defaultPorts[$parts['scheme']] == $parts['port']) {
            // Removing default ports.
            unset($parts['port']);
        }
        // A few HTTP specific rules.
        switch ($parts['scheme']) {
            case 'http':
            case 'https':
                if (null === $parts['path']) {
                    // An empty path is equivalent to / in http.
                    $parts['path'] = '/';
                }
                break;
        }
    }

    if (null !== $parts['host']) {
        $parts['host'] = strtolower($parts['host']);
    }

    return build($parts);
}

/**
 * Parses a URI and returns its individual components.
 *
 * This method largely behaves the same as PHP's parse_url, except that it will
 * return an array with all the array keys, including the ones that are not
 * set by parse_url, which makes it a bit easier to work with.
 *
 * Unlike PHP's parse_url, it will also convert any non-ascii characters to
 * percent-encoded strings. PHP's parse_url corrupts these characters on OS X.
 *
 * In the return array, key "port" is an int value. Other keys have a string value.
 * "Unused" keys have value null.
 *
 * @return array{scheme: string|null, host: string|null, path: string|null, port: positive-int|null, user: string|null, query: string|null, fragment: string|null}
 *
 * @throws InvalidUriException
 */
function parse(string $uri): array
{
    // Normally a URI must be ASCII. However, often it's not and
    // parse_url might corrupt these strings.
    //
    // For that reason we take any non-ascii characters from the uri and
    // uriencode them first.
    $uri = preg_replace_callback(
        '/[^[:ascii:]]/u',
        function ($matches) {
            return rawurlencode($matches[0]);
        },
        $uri
    );

    if (null === $uri) {
        throw new InvalidUriException('Invalid, or could not parse URI');
    }

    $result = parse_url($uri);
    if (false === $result) {
        $result = _parse_fallback($uri);
    }

    /*
     * phpstan is not able to process all the things that happen while this function
     * constructs the result array. It only understands the $result is
     * non-empty-array<string, mixed>
     *
     * But the detail of the returned array is correctly specified in the PHPdoc
     * above the function call.
     *
     * @phpstan-ignore-next-line
     */
    return
         $result + [
             'scheme' => null,
             'host' => null,
             'path' => null,
             'port' => null,
             'user' => null,
             'query' => null,
             'fragment' => null,
         ];
}

/**
 * This function takes the components returned from PHP's parse_url, and uses
 * it to generate a new uri.
 *
 * @param array<string, int|string|null> $parts
 */
function build(array $parts): string
{
    $uri = '';

    $authority = '';
    if (isset($parts['host'])) {
        $authority = $parts['host'];
        if (isset($parts['user'])) {
            $authority = $parts['user'].'@'.$authority;
        }
        if (isset($parts['port'])) {
            $authority = $authority.':'.$parts['port'];
        }
    }

    if (isset($parts['scheme'])) {
        // If there's a scheme, there's also a host.
        $uri = $parts['scheme'].':';
    }
    if ('' !== $authority || (isset($parts['scheme']) && 'file' === $parts['scheme'])) {
        // No scheme, but there is a host.
        $uri .= '//'.$authority;
    }

    if (isset($parts['path'])) {
        $uri .= $parts['path'];
    }
    if (isset($parts['query'])) {
        $uri .= '?'.$parts['query'];
    }
    if (isset($parts['fragment'])) {
        $uri .= '#'.$parts['fragment'];
    }

    return $uri;
}

/**
 * Returns the 'dirname' and 'basename' for a path.
 *
 * The reason there is a custom function for this purpose, is because
 * basename() is locale aware (behaviour changes if C locale or a UTF-8 locale
 * is used) and we need a method that just operates on UTF-8 characters.
 *
 * In addition basename and dirname are platform aware, and will treat
 * backslash (\) as a directory separator on Windows.
 *
 * This method returns the 2 components as an array.
 *
 * If there is no dirname, it will return an empty string. Any / appearing at
 * the end of the string is stripped off.
 *
 * @return array<int, mixed>
 */
function split(string $path): array
{
    $matches = [];
    if (1 === preg_match('/^(?:(?:(.*)(?:\/+))?([^\/]+))(?:\/?)$/u', $path, $matches)) {
        return [$matches[1], $matches[2]];
    }

    return [null, null];
}

/**
 * This function is another implementation of parse_url, except this one is
 * fully written in PHP.
 *
 * The reason is that the PHP bug team is not willing to admit that there are
 * bugs in the parse_url implementation.
 *
 * This function is only called if the main parse method fails. It's pretty
 * crude and probably slow, so the original parse_url is usually preferred.
 *
 * @return array{scheme: string|null, host: string|null, path: string|null, port: positive-int|null, user: string|null, query: string|null, fragment: string|null}
 *
 * @throws InvalidUriException
 */
function _parse_fallback(string $uri): array
{
    // Normally a URI must be ASCII, however. However, often it's not and
    // parse_url might corrupt these strings.
    //
    // For that reason we take any non-ascii characters from the uri and
    // uriencode them first.
    $uri = preg_replace_callback(
        '/[^[:ascii:]]/u',
        function ($matches) {
            return rawurlencode($matches[0]);
        },
        $uri
    );

    if (null === $uri) {
        throw new InvalidUriException('Invalid, or could not parse URI');
    }

    $result = [
        'scheme' => null,
        'host' => null,
        'port' => null,
        'user' => null,
        'path' => null,
        'fragment' => null,
        'query' => null,
    ];

    if (1 === preg_match('% ^([A-Za-z][A-Za-z0-9+-\.]+): %x', $uri, $matches)) {
        $result['scheme'] = $matches[1];
        // Take what's left.
        $uri = substr($uri, strlen($result['scheme']) + 1);
        if (false === $uri) {
            // There was nothing left.
            $uri = '';
        }
    }

    // Taking off a fragment part
    if (false !== strpos($uri, '#')) {
        list($uri, $result['fragment']) = explode('#', $uri, 2);
    }
    // Taking off the query part
    if (false !== strpos($uri, '?')) {
        list($uri, $result['query']) = explode('?', $uri, 2);
    }

    if ('///' === substr($uri, 0, 3)) {
        // The triple slash uris are a bit unusual, but we have special handling
        // for them.
        $path = substr($uri, 2);
        if (false === $path) {
            throw new \RuntimeException('The string cannot be false');
        }
        $result['path'] = $path;
        $result['host'] = '';
    } elseif ('//' === substr($uri, 0, 2)) {
        // Uris that have an authority part.
        $regex = '%^
            //
            (?: (?<user> [^:@]+) (: (?<pass> [^@]+)) @)?
            (?<host> ( [^:/]* | \[ [^\]]+ \] ))
            (?: : (?<port> [0-9]+))?
            (?<path> / .*)?
          $%x';
        if (1 !== preg_match($regex, $uri, $matches)) {
            throw new InvalidUriException('Invalid, or could not parse URI');
        }
        if (isset($matches['host']) && '' !== $matches['host']) {
            $result['host'] = $matches['host'];
        }
        if (isset($matches['port'])) {
            $port = (int) $matches['port'];
            if ($port > 0) {
                $result['port'] = $port;
            }
        }
        if (isset($matches['path'])) {
            $result['path'] = $matches['path'];
        }
        if (isset($matches['user']) && '' !== $matches['user']) {
            $result['user'] = $matches['user'];
        }
        if (isset($matches['pass']) && '' !== $matches['pass']) {
            $result['pass'] = $matches['pass'];
        }
    } else {
        $result['path'] = $uri;
    }

    return $result;
}
