<?php

declare(strict_types=1);

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Uri;

use JsonSchema\Exception\UriResolverException;
use JsonSchema\UriResolverInterface;

/**
 * Resolves JSON Schema URIs
 *
 * @author Sander Coolen <sander@jibber.nl>
 */
class UriResolver implements UriResolverInterface
{
    /**
     * Parses a URI into five main components
     *
     * @param string $uri
     *
     * @return array
     */
    public function parse($uri)
    {
        preg_match('|^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?|', (string) $uri, $match);

        $components = [];
        if (5 < count($match)) {
            $components =  [
                'scheme'    => $match[2],
                'authority' => $match[4],
                'path'      => $match[5]
            ];
        }
        if (7 < count($match)) {
            $components['query'] = $match[7];
        }
        if (9 < count($match)) {
            $components['fragment'] = $match[9];
        }

        return $components;
    }

    /**
     * Builds a URI based on n array with the main components
     *
     * @param array $components
     *
     * @return string
     */
    public function generate(array $components)
    {
        $uri = $components['scheme'] . '://'
             . $components['authority']
             . $components['path'];

        if (array_key_exists('query', $components) && strlen($components['query'])) {
            $uri .= '?' . $components['query'];
        }
        if (array_key_exists('fragment', $components)) {
            $uri .= '#' . $components['fragment'];
        }

        return $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($uri, $baseUri = null)
    {
        // treat non-uri base as local file path
        if (
            !is_null($baseUri) &&
            !filter_var($baseUri, \FILTER_VALIDATE_URL) &&
            !preg_match('|^[^/]+://|u', $baseUri)
        ) {
            if (is_file($baseUri)) {
                $baseUri = 'file://' . realpath($baseUri);
            } elseif (is_dir($baseUri)) {
                $baseUri = 'file://' . realpath($baseUri) . '/';
            } else {
                $baseUri = 'file://' . getcwd() . '/' . $baseUri;
            }
        }

        if ($uri == '') {
            return $baseUri;
        }

        $components = $this->parse($uri);
        $path = $components['path'];

        if (!empty($components['scheme'])) {
            return $uri;
        }
        $baseComponents = $this->parse($baseUri);
        $basePath = $baseComponents['path'];

        $baseComponents['path'] = self::combineRelativePathWithBasePath($path, $basePath);
        if (isset($components['fragment'])) {
            $baseComponents['fragment'] = $components['fragment'];
        }

        return $this->generate($baseComponents);
    }

    /**
     * Tries to glue a relative path onto an absolute one
     *
     * @param string $relativePath
     * @param string $basePath
     *
     * @throws UriResolverException
     *
     * @return string Merged path
     */
    public static function combineRelativePathWithBasePath($relativePath, $basePath)
    {
        $relativePath = self::normalizePath($relativePath);
        if (!$relativePath) {
            return $basePath;
        }
        if ($relativePath[0] === '/') {
            return $relativePath;
        }
        if (!$basePath) {
            throw new UriResolverException(sprintf("Unable to resolve URI '%s' from base '%s'", $relativePath, $basePath));
        }

        $dirname = $basePath[strlen($basePath) - 1] === '/' ? $basePath : dirname($basePath);
        $combined = rtrim($dirname, '/') . '/' . ltrim($relativePath, '/');
        $combinedSegments = explode('/', $combined);
        $collapsedSegments = [];
        while ($combinedSegments) {
            $segment = array_shift($combinedSegments);
            if ($segment === '..') {
                if (count($collapsedSegments) <= 1) {
                    // Do not remove the top level (domain)
                    // This is not ideal - the domain should not be part of the path here. parse() and generate()
                    // should handle the "domain" separately, like the schema.
                    // Then the if-condition here would be `if (!$collapsedSegments) {`.
                    throw new UriResolverException(sprintf("Unable to resolve URI '%s' from base '%s'", $relativePath, $basePath));
                }
                array_pop($collapsedSegments);
            } else {
                $collapsedSegments[] = $segment;
            }
        }

        return implode('/', $collapsedSegments);
    }

    /**
     * Normalizes a URI path component by removing dot-slash and double slashes
     *
     * @param string $path
     *
     * @return string
     */
    private static function normalizePath($path)
    {
        $path = preg_replace('|((?<!\.)\./)*|', '', $path);
        $path = preg_replace('|//|', '/', $path);

        return $path;
    }

    /**
     * @param string $uri
     *
     * @return bool
     */
    public function isValid($uri)
    {
        $components = $this->parse($uri);

        return !empty($components);
    }
}
