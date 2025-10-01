<?php

namespace GuzzleHttp;

use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Handler\Proxy;
use GuzzleHttp\Handler\StreamHandler;
use Psr\Http\Message\UriInterface;

final class Utils
{
    /**
     * Debug function used to describe the provided value type and class.
     *
     * @param mixed $input
     *
     * @return string Returns a string containing the type of the variable and
     *                if a class is provided, the class name.
     */
    public static function describeType($input): string
    {
        switch (\gettype($input)) {
            case 'object':
                return 'object('.\get_class($input).')';
            case 'array':
                return 'array('.\count($input).')';
            default:
                \ob_start();
                \var_dump($input);
                // normalize float vs double
                /** @var string $varDumpContent */
                $varDumpContent = \ob_get_clean();

                return \str_replace('double(', 'float(', \rtrim($varDumpContent));
        }
    }

    /**
     * Parses an array of header lines into an associative array of headers.
     *
     * @param iterable $lines Header lines array of strings in the following
     *                        format: "Name: Value"
     */
    public static function headersFromLines(iterable $lines): array
    {
        $headers = [];

        foreach ($lines as $line) {
            $parts = \explode(':', $line, 2);
            $headers[\trim($parts[0])][] = isset($parts[1]) ? \trim($parts[1]) : null;
        }

        return $headers;
    }

    /**
     * Returns a debug stream based on the provided variable.
     *
     * @param mixed $value Optional value
     *
     * @return resource
     */
    public static function debugResource($value = null)
    {
        if (\is_resource($value)) {
            return $value;
        }
        if (\defined('STDOUT')) {
            return \STDOUT;
        }

        return Psr7\Utils::tryFopen('php://output', 'w');
    }

    /**
     * Chooses and creates a default handler to use based on the environment.
     *
     * The returned handler is not wrapped by any default middlewares.
     *
     * @return callable(\Psr\Http\Message\RequestInterface, array): Promise\PromiseInterface Returns the best handler for the given system.
     *
     * @throws \RuntimeException if no viable Handler is available.
     */
    public static function chooseHandler(): callable
    {
        $handler = null;

        if (\defined('CURLOPT_CUSTOMREQUEST') && \function_exists('curl_version') && version_compare(curl_version()['version'], '7.21.2') >= 0) {
            if (\function_exists('curl_multi_exec') && \function_exists('curl_exec')) {
                $handler = Proxy::wrapSync(new CurlMultiHandler(), new CurlHandler());
            } elseif (\function_exists('curl_exec')) {
                $handler = new CurlHandler();
            } elseif (\function_exists('curl_multi_exec')) {
                $handler = new CurlMultiHandler();
            }
        }

        if (\ini_get('allow_url_fopen')) {
            $handler = $handler
                ? Proxy::wrapStreaming($handler, new StreamHandler())
                : new StreamHandler();
        } elseif (!$handler) {
            throw new \RuntimeException('GuzzleHttp requires cURL, the allow_url_fopen ini setting, or a custom HTTP handler.');
        }

        return $handler;
    }

    /**
     * Get the default User-Agent string to use with Guzzle.
     */
    public static function defaultUserAgent(): string
    {
        return sprintf('GuzzleHttp/%d', ClientInterface::MAJOR_VERSION);
    }

    /**
     * Returns the default cacert bundle for the current system.
     *
     * First, the openssl.cafile and curl.cainfo php.ini settings are checked.
     * If those settings are not configured, then the common locations for
     * bundles found on Red Hat, CentOS, Fedora, Ubuntu, Debian, FreeBSD, OS X
     * and Windows are checked. If any of these file locations are found on
     * disk, they will be utilized.
     *
     * Note: the result of this function is cached for subsequent calls.
     *
     * @throws \RuntimeException if no bundle can be found.
     *
     * @deprecated Utils::defaultCaBundle will be removed in guzzlehttp/guzzle:8.0. This method is not needed in PHP 5.6+.
     */
    public static function defaultCaBundle(): string
    {
        static $cached = null;
        static $cafiles = [
            // Red Hat, CentOS, Fedora (provided by the ca-certificates package)
            '/etc/pki/tls/certs/ca-bundle.crt',
            // Ubuntu, Debian (provided by the ca-certificates package)
            '/etc/ssl/certs/ca-certificates.crt',
            // FreeBSD (provided by the ca_root_nss package)
            '/usr/local/share/certs/ca-root-nss.crt',
            // SLES 12 (provided by the ca-certificates package)
            '/var/lib/ca-certificates/ca-bundle.pem',
            // OS X provided by homebrew (using the default path)
            '/usr/local/etc/openssl/cert.pem',
            // Google app engine
            '/etc/ca-certificates.crt',
            // Windows?
            'C:\\windows\\system32\\curl-ca-bundle.crt',
            'C:\\windows\\curl-ca-bundle.crt',
        ];

        if ($cached) {
            return $cached;
        }

        if ($ca = \ini_get('openssl.cafile')) {
            return $cached = $ca;
        }

        if ($ca = \ini_get('curl.cainfo')) {
            return $cached = $ca;
        }

        foreach ($cafiles as $filename) {
            if (\file_exists($filename)) {
                return $cached = $filename;
            }
        }

        throw new \RuntimeException(
            <<< EOT
No system CA bundle could be found in any of the the common system locations.
PHP versions earlier than 5.6 are not properly configured to use the system's
CA bundle by default. In order to verify peer certificates, you will need to
supply the path on disk to a certificate bundle to the 'verify' request
option: https://docs.guzzlephp.org/en/latest/request-options.html#verify. If
you do not need a specific certificate bundle, then Mozilla provides a commonly
used CA bundle which can be downloaded here (provided by the maintainer of
cURL): https://curl.haxx.se/ca/cacert.pem. Once you have a CA bundle available
on disk, you can set the 'openssl.cafile' PHP ini setting to point to the path
to the file, allowing you to omit the 'verify' request option. See
https://curl.haxx.se/docs/sslcerts.html for more information.
EOT
        );
    }

    /**
     * Creates an associative array of lowercase header names to the actual
     * header casing.
     */
    public static function normalizeHeaderKeys(array $headers): array
    {
        $result = [];
        foreach (\array_keys($headers) as $key) {
            $result[\strtolower($key)] = $key;
        }

        return $result;
    }

    /**
     * Returns true if the provided host matches any of the no proxy areas.
     *
     * This method will strip a port from the host if it is present. Each pattern
     * can be matched with an exact match (e.g., "foo.com" == "foo.com") or a
     * partial match: (e.g., "foo.com" == "baz.foo.com" and ".foo.com" ==
     * "baz.foo.com", but ".foo.com" != "foo.com").
     *
     * Areas are matched in the following cases:
     * 1. "*" (without quotes) always matches any hosts.
     * 2. An exact match.
     * 3. The area starts with "." and the area is the last part of the host. e.g.
     *    '.mit.edu' will match any host that ends with '.mit.edu'.
     *
     * @param string   $host         Host to check against the patterns.
     * @param string[] $noProxyArray An array of host patterns.
     *
     * @throws InvalidArgumentException
     */
    public static function isHostInNoProxy(string $host, array $noProxyArray): bool
    {
        if (\strlen($host) === 0) {
            throw new InvalidArgumentException('Empty host provided');
        }

        // Strip port if present.
        [$host] = \explode(':', $host, 2);

        foreach ($noProxyArray as $area) {
            // Always match on wildcards.
            if ($area === '*') {
                return true;
            }

            if (empty($area)) {
                // Don't match on empty values.
                continue;
            }

            if ($area === $host) {
                // Exact matches.
                return true;
            }
            // Special match if the area when prefixed with ".". Remove any
            // existing leading "." and add a new leading ".".
            $area = '.'.\ltrim($area, '.');
            if (\substr($host, -\strlen($area)) === $area) {
                return true;
            }
        }

        return false;
    }

    /**
     * Wrapper for json_decode that throws when an error occurs.
     *
     * @param string $json    JSON data to parse
     * @param bool   $assoc   When true, returned objects will be converted
     *                        into associative arrays.
     * @param int    $depth   User specified recursion depth.
     * @param int    $options Bitmask of JSON decode options.
     *
     * @return object|array|string|int|float|bool|null
     *
     * @throws InvalidArgumentException if the JSON cannot be decoded.
     *
     * @see https://www.php.net/manual/en/function.json-decode.php
     */
    public static function jsonDecode(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        $data = \json_decode($json, $assoc, $depth, $options);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            throw new InvalidArgumentException('json_decode error: '.\json_last_error_msg());
        }

        return $data;
    }

    /**
     * Wrapper for JSON encoding that throws when an error occurs.
     *
     * @param mixed $value   The value being encoded
     * @param int   $options JSON encode option bitmask
     * @param int   $depth   Set the maximum depth. Must be greater than zero.
     *
     * @throws InvalidArgumentException if the JSON cannot be encoded.
     *
     * @see https://www.php.net/manual/en/function.json-encode.php
     */
    public static function jsonEncode($value, int $options = 0, int $depth = 512): string
    {
        $json = \json_encode($value, $options, $depth);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            throw new InvalidArgumentException('json_encode error: '.\json_last_error_msg());
        }

        /** @var string */
        return $json;
    }

    /**
     * Wrapper for the hrtime() or microtime() functions
     * (depending on the PHP version, one of the two is used)
     *
     * @return float UNIX timestamp
     *
     * @internal
     */
    public static function currentTime(): float
    {
        return (float) \function_exists('hrtime') ? \hrtime(true) / 1e9 : \microtime(true);
    }

    /**
     * @throws InvalidArgumentException
     *
     * @internal
     */
    public static function idnUriConvert(UriInterface $uri, int $options = 0): UriInterface
    {
        if ($uri->getHost()) {
            $asciiHost = self::idnToAsci($uri->getHost(), $options, $info);
            if ($asciiHost === false) {
                $errorBitSet = $info['errors'] ?? 0;

                $errorConstants = array_filter(array_keys(get_defined_constants()), static function (string $name): bool {
                    return substr($name, 0, 11) === 'IDNA_ERROR_';
                });

                $errors = [];
                foreach ($errorConstants as $errorConstant) {
                    if ($errorBitSet & constant($errorConstant)) {
                        $errors[] = $errorConstant;
                    }
                }

                $errorMessage = 'IDN conversion failed';
                if ($errors) {
                    $errorMessage .= ' (errors: '.implode(', ', $errors).')';
                }

                throw new InvalidArgumentException($errorMessage);
            }
            if ($uri->getHost() !== $asciiHost) {
                // Replace URI only if the ASCII version is different
                $uri = $uri->withHost($asciiHost);
            }
        }

        return $uri;
    }

    /**
     * @internal
     */
    public static function getenv(string $name): ?string
    {
        if (isset($_SERVER[$name])) {
            return (string) $_SERVER[$name];
        }

        if (\PHP_SAPI === 'cli' && ($value = \getenv($name)) !== false && $value !== null) {
            return (string) $value;
        }

        return null;
    }

    /**
     * @return string|false
     */
    private static function idnToAsci(string $domain, int $options, ?array &$info = [])
    {
        if (\function_exists('idn_to_ascii') && \defined('INTL_IDNA_VARIANT_UTS46')) {
            return \idn_to_ascii($domain, $options, \INTL_IDNA_VARIANT_UTS46, $info);
        }

        throw new \Error('ext-idn or symfony/polyfill-intl-idn not loaded or too old');
    }
}
