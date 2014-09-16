<?php

namespace Guzzle\Http;

use Guzzle\Common\Exception\InvalidArgumentException;

/**
 * Parses and generates URLs based on URL parts. In favor of performance, URL parts are not validated.
 */
class Url
{
    protected $scheme;
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    protected $path = '';
    protected $fragment;

    /** @var QueryString Query part of the URL */
    protected $query;

    /**
     * Factory method to create a new URL from a URL string
     *
     * @param string $url Full URL used to create a Url object
     *
     * @return Url
     * @throws InvalidArgumentException
     */
    public static function factory($url)
    {
        static $defaults = array('scheme' => null, 'host' => null, 'path' => null, 'port' => null, 'query' => null,
            'user' => null, 'pass' => null, 'fragment' => null);

        if (false === ($parts = parse_url($url))) {
            throw new InvalidArgumentException('Was unable to parse malformed url: ' . $url);
        }

        $parts += $defaults;

        // Convert the query string into a QueryString object
        if ($parts['query'] || 0 !== strlen($parts['query'])) {
            $parts['query'] = QueryString::fromString($parts['query']);
        }

        return new static($parts['scheme'], $parts['host'], $parts['user'],
            $parts['pass'], $parts['port'], $parts['path'], $parts['query'],
            $parts['fragment']);
    }

    /**
     * Build a URL from parse_url parts. The generated URL will be a relative URL if a scheme or host are not provided.
     *
     * @param array $parts Array of parse_url parts
     *
     * @return string
     */
    public static function buildUrl(array $parts)
    {
        $url = $scheme = '';

        if (isset($parts['scheme'])) {
            $scheme = $parts['scheme'];
            $url .= $scheme . ':';
        }

        if (isset($parts['host'])) {
            $url .= '//';
            if (isset($parts['user'])) {
                $url .= $parts['user'];
                if (isset($parts['pass'])) {
                    $url .= ':' . $parts['pass'];
                }
                $url .=  '@';
            }

            $url .= $parts['host'];

            // Only include the port if it is not the default port of the scheme
            if (isset($parts['port'])
                && !(($scheme == 'http' && $parts['port'] == 80) || ($scheme == 'https' && $parts['port'] == 443))
            ) {
                $url .= ':' . $parts['port'];
            }
        }

        // Add the path component if present
        if (isset($parts['path']) && 0 !== strlen($parts['path'])) {
            // Always ensure that the path begins with '/' if set and something is before the path
            if ($url && $parts['path'][0] != '/' && substr($url, -1)  != '/') {
                $url .= '/';
            }
            $url .= $parts['path'];
        }

        // Add the query string if present
        if (isset($parts['query'])) {
            $url .= '?' . $parts['query'];
        }

        // Ensure that # is only added to the url if fragment contains anything.
        if (isset($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }

    /**
     * Create a new URL from URL parts
     *
     * @param string                   $scheme   Scheme of the URL
     * @param string                   $host     Host of the URL
     * @param string                   $username Username of the URL
     * @param string                   $password Password of the URL
     * @param int                      $port     Port of the URL
     * @param string                   $path     Path of the URL
     * @param QueryString|array|string $query    Query string of the URL
     * @param string                   $fragment Fragment of the URL
     */
    public function __construct($scheme, $host, $username = null, $password = null, $port = null, $path = null, QueryString $query = null, $fragment = null)
    {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->fragment = $fragment;
        if (!$query) {
            $this->query = new QueryString();
        } else {
            $this->setQuery($query);
        }
        $this->setPath($path);
    }

    /**
     * Clone the URL
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }

    /**
     * Returns the URL as a URL string
     *
     * @return string
     */
    public function __toString()
    {
        return self::buildUrl($this->getParts());
    }

    /**
     * Get the parts of the URL as an array
     *
     * @return array
     */
    public function getParts()
    {
        $query = (string) $this->query;

        return array(
            'scheme' => $this->scheme,
            'user' => $this->username,
            'pass' => $this->password,
            'host' => $this->host,
            'port' => $this->port,
            'path' => $this->getPath(),
            'query' => $query !== '' ? $query : null,
            'fragment' => $this->fragment,
        );
    }

    /**
     * Set the host of the request.
     *
     * @param string $host Host to set (e.g. www.yahoo.com, yahoo.com)
     *
     * @return Url
     */
    public function setHost($host)
    {
        if (strpos($host, ':') === false) {
            $this->host = $host;
        } else {
            list($host, $port) = explode(':', $host);
            $this->host = $host;
            $this->setPort($port);
        }

        return $this;
    }

    /**
     * Get the host part of the URL
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the scheme part of the URL (http, https, ftp, etc)
     *
     * @param string $scheme Scheme to set
     *
     * @return Url
     */
    public function setScheme($scheme)
    {
        if ($this->scheme == 'http' && $this->port == 80) {
            $this->port = null;
        } elseif ($this->scheme == 'https' && $this->port == 443) {
            $this->port = null;
        }

        $this->scheme = $scheme;

        return $this;
    }

    /**
     * Get the scheme part of the URL
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Set the port part of the URL
     *
     * @param int $port Port to set
     *
     * @return Url
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get the port part of the URl. Will return the default port for a given scheme if no port has been set.
     *
     * @return int|null
     */
    public function getPort()
    {
        if ($this->port) {
            return $this->port;
        } elseif ($this->scheme == 'http') {
            return 80;
        } elseif ($this->scheme == 'https') {
            return 443;
        }

        return null;
    }

    /**
     * Set the path part of the URL
     *
     * @param array|string $path Path string or array of path segments
     *
     * @return Url
     */
    public function setPath($path)
    {
        static $pathReplace = array(' ' => '%20', '?' => '%3F');
        if (is_array($path)) {
            $path = '/' . implode('/', $path);
        }

        $this->path = strtr($path, $pathReplace);

        return $this;
    }

    /**
     * Normalize the URL so that double slashes and relative paths are removed
     *
     * @return Url
     */
    public function normalizePath()
    {
        if (!$this->path || $this->path == '/' || $this->path == '*') {
            return $this;
        }

        $results = array();
        $segments = $this->getPathSegments();
        foreach ($segments as $segment) {
            if ($segment == '..') {
                array_pop($results);
            } elseif ($segment != '.' && $segment != '') {
                $results[] = $segment;
            }
        }

        // Combine the normalized parts and add the leading slash if needed
        $this->path = ($this->path[0] == '/' ? '/' : '') . implode('/', $results);

        // Add the trailing slash if necessary
        if ($this->path != '/' && end($segments) == '') {
            $this->path .= '/';
        }

        return $this;
    }

    /**
     * Add a relative path to the currently set path.
     *
     * @param string $relativePath Relative path to add
     *
     * @return Url
     */
    public function addPath($relativePath)
    {
        if ($relativePath != '/' && is_string($relativePath) && strlen($relativePath) > 0) {
            // Add a leading slash if needed
            if ($relativePath[0] != '/') {
                $relativePath = '/' . $relativePath;
            }
            $this->setPath(str_replace('//', '/', $this->path . $relativePath));
        }

        return $this;
    }

    /**
     * Get the path part of the URL
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the path segments of the URL as an array
     *
     * @return array
     */
    public function getPathSegments()
    {
        return array_slice(explode('/', $this->getPath()), 1);
    }

    /**
     * Set the password part of the URL
     *
     * @param string $password Password to set
     *
     * @return Url
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get the password part of the URL
     *
     * @return null|string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the username part of the URL
     *
     * @param string $username Username to set
     *
     * @return Url
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get the username part of the URl
     *
     * @return null|string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get the query part of the URL as a QueryString object
     *
     * @return QueryString
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set the query part of the URL
     *
     * @param QueryString|string|array $query Query to set
     *
     * @return Url
     */
    public function setQuery($query)
    {
        if (is_string($query)) {
            $output = null;
            parse_str($query, $output);
            $this->query = new QueryString($output);
        } elseif (is_array($query)) {
            $this->query = new QueryString($query);
        } elseif ($query instanceof QueryString) {
            $this->query = $query;
        }

        return $this;
    }

    /**
     * Get the fragment part of the URL
     *
     * @return null|string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Set the fragment part of the URL
     *
     * @param string $fragment Fragment to set
     *
     * @return Url
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;

        return $this;
    }

    /**
     * Check if this is an absolute URL
     *
     * @return bool
     */
    public function isAbsolute()
    {
        return $this->scheme && $this->host;
    }

    /**
     * Combine the URL with another URL. Follows the rules specific in RFC 3986 section 5.4.
     *
     * @param string $url           Relative URL to combine with
     * @param bool   $strictRfc3986 Set to true to use strict RFC 3986 compliance when merging paths. When first
     *                              released, Guzzle used an incorrect algorithm for combining relative URL paths. In
     *                              order to not break users, we introduced this flag to allow the merging of URLs based
     *                              on strict RFC 3986 section 5.4.1. This means that "http://a.com/foo/baz" merged with
     *                              "bar" would become "http://a.com/foo/bar". When this value is set to false, it would
     *                              become "http://a.com/foo/baz/bar".
     * @return Url
     * @throws InvalidArgumentException
     * @link http://tools.ietf.org/html/rfc3986#section-5.4
     */
    public function combine($url, $strictRfc3986 = false)
    {
        $url = self::factory($url);

        // Use the more absolute URL as the base URL
        if (!$this->isAbsolute() && $url->isAbsolute()) {
            $url = $url->combine($this);
        }

        // Passing a URL with a scheme overrides everything
        if ($buffer = $url->getScheme()) {
            $this->scheme = $buffer;
            $this->host = $url->getHost();
            $this->port = $url->getPort();
            $this->username = $url->getUsername();
            $this->password = $url->getPassword();
            $this->path = $url->getPath();
            $this->query = $url->getQuery();
            $this->fragment = $url->getFragment();
            return $this;
        }

        // Setting a host overrides the entire rest of the URL
        if ($buffer = $url->getHost()) {
            $this->host = $buffer;
            $this->port = $url->getPort();
            $this->username = $url->getUsername();
            $this->password = $url->getPassword();
            $this->path = $url->getPath();
            $this->query = $url->getQuery();
            $this->fragment = $url->getFragment();
            return $this;
        }

        $path = $url->getPath();
        $query = $url->getQuery();

        if (!$path) {
            if (count($query)) {
                $this->addQuery($query, $strictRfc3986);
            }
        } else {
            if ($path[0] == '/') {
                $this->path = $path;
            } elseif ($strictRfc3986) {
                $this->path .= '/../' . $path;
            } else {
                $this->path .= '/' . $path;
            }
            $this->normalizePath();
            $this->addQuery($query, $strictRfc3986);
        }

        $this->fragment = $url->getFragment();

        return $this;
    }

    private function addQuery(QueryString $new, $strictRfc386)
    {
        if (!$strictRfc386) {
            $new->merge($this->query);
        }

        $this->query = $new;
    }
}
