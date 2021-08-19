<?php
namespace Aws\S3;

use Aws\Arn\Exception\InvalidArnException;
use Aws\Arn\S3\AccessPointArn;
use Aws\Arn\ArnParser;
use GuzzleHttp\Psr7;
use Psr\Http\Message\UriInterface;

/**
 * Extracts a region, bucket, key, and and if a URI is in path-style
 */
class S3UriParser
{
    private $pattern = '/^(.+\\.)?s3[.-]([A-Za-z0-9-]+)\\./';
    private $streamWrapperScheme = 's3';

    private static $defaultResult = [
        'path_style' => true,
        'bucket'     => null,
        'key'        => null,
        'region'     => null
    ];

    /**
     * Parses a URL or S3 StreamWrapper Uri (s3://) into an associative array
     * of Amazon S3 data including:
     *
     * - bucket: The Amazon S3 bucket (null if none)
     * - key: The Amazon S3 key (null if none)
     * - path_style: Set to true if using path style, or false if not
     * - region: Set to a string if a non-class endpoint is used or null.
     *
     * @param string|UriInterface $uri
     *
     * @return array
     * @throws \InvalidArgumentException|InvalidArnException
     */
    public function parse($uri)
    {
        // Attempt to parse host component of uri as an ARN
        $components = $this->parseS3UrlComponents($uri);
        if (!empty($components)) {
            if (ArnParser::isArn($components['host'])) {
                $arn = new AccessPointArn($components['host']);
                return [
                    'bucket' => $components['host'],
                    'key' => $components['path'],
                    'path_style' => false,
                    'region' => $arn->getRegion()
                ];
            }
        }

        $url = Psr7\Utils::uriFor($uri);

        if ($url->getScheme() == $this->streamWrapperScheme) {
            return $this->parseStreamWrapper($url);
        }

        if (!$url->getHost()) {
            throw new \InvalidArgumentException('No hostname found in URI: '
                . $uri);
        }

        if (!preg_match($this->pattern, $url->getHost(), $matches)) {
            return $this->parseCustomEndpoint($url);
        }

        // Parse the URI based on the matched format (path / virtual)
        $result = empty($matches[1])
            ? $this->parsePathStyle($url)
            : $this->parseVirtualHosted($url, $matches);

        // Add the region if one was found and not the classic endpoint
        $result['region'] = $matches[2] == 'amazonaws' ? null : $matches[2];

        return $result;
    }

    private function parseS3UrlComponents($uri)
    {
        preg_match("/^([a-zA-Z0-9]*):\/\/([a-zA-Z0-9:-]*)\/(.*)/", $uri, $components);
        if (empty($components)) {
            return [];
        }
        return [
            'scheme' => $components[1],
            'host' => $components[2],
            'path' => $components[3],
        ];
    }

    private function parseStreamWrapper(UriInterface $url)
    {
        $result = self::$defaultResult;
        $result['path_style'] = false;

        $result['bucket'] = $url->getHost();
        if ($url->getPath()) {
            $key = ltrim($url->getPath(), '/ ');
            if (!empty($key)) {
                $result['key'] = $key;
            }
        }

        return $result;
    }

    private function parseCustomEndpoint(UriInterface $url)
    {
        $result = self::$defaultResult;
        $path = ltrim($url->getPath(), '/ ');
        $segments = explode('/', $path, 2);

        if (isset($segments[0])) {
            $result['bucket'] = $segments[0];
            if (isset($segments[1])) {
                $result['key'] = $segments[1];
            }
        }

        return $result;
    }

    private function parsePathStyle(UriInterface $url)
    {
        $result = self::$defaultResult;

        if ($url->getPath() != '/') {
            $path = ltrim($url->getPath(), '/');
            if ($path) {
                $pathPos = strpos($path, '/');
                if ($pathPos === false) {
                    // https://s3.amazonaws.com/bucket
                    $result['bucket'] = $path;
                } elseif ($pathPos == strlen($path) - 1) {
                    // https://s3.amazonaws.com/bucket/
                    $result['bucket'] = substr($path, 0, -1);
                } else {
                    // https://s3.amazonaws.com/bucket/key
                    $result['bucket'] = substr($path, 0, $pathPos);
                    $result['key'] = substr($path, $pathPos + 1) ?: null;
                }
            }
        }

        return $result;
    }

    private function parseVirtualHosted(UriInterface $url, array $matches)
    {
        $result = self::$defaultResult;
        $result['path_style'] = false;
        // Remove trailing "." from the prefix to get the bucket
        $result['bucket'] = substr($matches[1], 0, -1);
        $path = $url->getPath();
        // Check if a key was present, and if so, removing the leading "/"
        $result['key'] = !$path || $path == '/' ? null : substr($path, 1);

        return $result;
    }
}
