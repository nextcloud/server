<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

use League\Uri\Exceptions\IdnSupportMissing;
use League\Uri\Exceptions\SyntaxError;
use function array_merge;
use function defined;
use function explode;
use function filter_var;
use function function_exists;
use function gettype;
use function idn_to_ascii;
use function implode;
use function inet_pton;
use function is_object;
use function is_scalar;
use function method_exists;
use function preg_match;
use function rawurldecode;
use function sprintf;
use function strpos;
use function substr;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;
use const IDNA_ERROR_BIDI;
use const IDNA_ERROR_CONTEXTJ;
use const IDNA_ERROR_DISALLOWED;
use const IDNA_ERROR_DOMAIN_NAME_TOO_LONG;
use const IDNA_ERROR_EMPTY_LABEL;
use const IDNA_ERROR_HYPHEN_3_4;
use const IDNA_ERROR_INVALID_ACE_LABEL;
use const IDNA_ERROR_LABEL_HAS_DOT;
use const IDNA_ERROR_LABEL_TOO_LONG;
use const IDNA_ERROR_LEADING_COMBINING_MARK;
use const IDNA_ERROR_LEADING_HYPHEN;
use const IDNA_ERROR_PUNYCODE;
use const IDNA_ERROR_TRAILING_HYPHEN;
use const INTL_IDNA_VARIANT_UTS46;

/**
 * A class to parse a URI string according to RFC3986.
 *
 * @link    https://tools.ietf.org/html/rfc3986
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   6.0.0
 */
final class UriString
{
    /**
     * Default URI component values.
     */
    private const URI_COMPONENTS = [
        'scheme' => null, 'user' => null, 'pass' => null, 'host' => null,
        'port' => null, 'path' => '', 'query' => null, 'fragment' => null,
    ];

    /**
     * Simple URI which do not need any parsing.
     */
    private const URI_SCHORTCUTS = [
        '' => [],
        '#' => ['fragment' => ''],
        '?' => ['query' => ''],
        '?#' => ['query' => '', 'fragment' => ''],
        '/' => ['path' => '/'],
        '//' => ['host' => ''],
    ];

    /**
     * Range of invalid characters in URI string.
     */
    private const REGEXP_INVALID_URI_CHARS = '/[\x00-\x1f\x7f]/';

    /**
     * RFC3986 regular expression URI splitter.
     *
     * @link https://tools.ietf.org/html/rfc3986#appendix-B
     */
    private const REGEXP_URI_PARTS = ',^
        (?<scheme>(?<scontent>[^:/?\#]+):)?    # URI scheme component
        (?<authority>//(?<acontent>[^/?\#]*))? # URI authority part
        (?<path>[^?\#]*)                       # URI path component
        (?<query>\?(?<qcontent>[^\#]*))?       # URI query component
        (?<fragment>\#(?<fcontent>.*))?        # URI fragment component
    ,x';

    /**
     * URI scheme regular expresssion.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.1
     */
    private const REGEXP_URI_SCHEME = '/^([a-z][a-z\d\+\.\-]*)?$/i';

    /**
     * IPvFuture regular expression.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     */
    private const REGEXP_IP_FUTURE = '/^
        v(?<version>[A-F0-9])+\.
        (?:
            (?<unreserved>[a-z0-9_~\-\.])|
            (?<sub_delims>[!$&\'()*+,;=:])  # also include the : character
        )+
    $/ix';

    /**
     * General registered name regular expression.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     */
    private const REGEXP_REGISTERED_NAME = '/(?(DEFINE)
        (?<unreserved>[a-z0-9_~\-])   # . is missing as it is used to separate labels
        (?<sub_delims>[!$&\'()*+,;=])
        (?<encoded>%[A-F0-9]{2})
        (?<reg_name>(?:(?&unreserved)|(?&sub_delims)|(?&encoded))*)
    )
    ^(?:(?&reg_name)\.)*(?&reg_name)\.?$/ix';

    /**
     * Invalid characters in host regular expression.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     */
    private const REGEXP_INVALID_HOST_CHARS = '/
        [:\/?#\[\]@ ]  # gen-delims characters as well as the space character
    /ix';

    /**
     * Invalid path for URI without scheme and authority regular expression.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.3
     */
    private const REGEXP_INVALID_PATH = ',^(([^/]*):)(.*)?/,';

    /**
     * Host and Port splitter regular expression.
     */
    private const REGEXP_HOST_PORT = ',^(?<host>\[.*\]|[^:]*)(:(?<port>.*))?$,';

    /**
     * IDN Host detector regular expression.
     */
    private const REGEXP_IDN_PATTERN = '/[^\x20-\x7f]/';

    /**
     * Only the address block fe80::/10 can have a Zone ID attach to
     * let's detect the link local significant 10 bits.
     */
    private const ZONE_ID_ADDRESS_BLOCK = "\xfe\x80";

    /**
     * Generate an URI string representation from its parsed representation
     * returned by League\Uri\parse() or PHP's parse_url.
     *
     * If you supply your own array, you are responsible for providing
     * valid components without their URI delimiters.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-5.3
     * @link https://tools.ietf.org/html/rfc3986#section-7.5
     *
     * @param array{
     *  scheme:?string,
     *  user:?string,
     *  pass:?string,
     *  host:?string,
     *  port:?int,
     *  path:string,
     *  query:?string,
     *  fragment:?string
     * } $components
     */
    public static function build(array $components): string
    {
        $result = $components['path'] ?? '';
        if (isset($components['query'])) {
            $result .= '?'.$components['query'];
        }

        if (isset($components['fragment'])) {
            $result .= '#'.$components['fragment'];
        }

        $scheme = null;
        if (isset($components['scheme'])) {
            $scheme = $components['scheme'].':';
        }

        if (!isset($components['host'])) {
            return $scheme.$result;
        }

        $scheme .= '//';
        $authority = $components['host'];
        if (isset($components['port'])) {
            $authority .= ':'.$components['port'];
        }

        if (!isset($components['user'])) {
            return $scheme.$authority.$result;
        }

        $authority = '@'.$authority;
        if (!isset($components['pass'])) {
            return $scheme.$components['user'].$authority.$result;
        }

        return $scheme.$components['user'].':'.$components['pass'].$authority.$result;
    }

    /**
     * Parse an URI string into its components.
     *
     * This method parses a URI and returns an associative array containing any
     * of the various components of the URI that are present.
     *
     * <code>
     * $components = (new Parser())->parse('http://foo@test.example.com:42?query#');
     * var_export($components);
     * //will display
     * array(
     *   'scheme' => 'http',           // the URI scheme component
     *   'user' => 'foo',              // the URI user component
     *   'pass' => null,               // the URI pass component
     *   'host' => 'test.example.com', // the URI host component
     *   'port' => 42,                 // the URI port component
     *   'path' => '',                 // the URI path component
     *   'query' => 'query',           // the URI query component
     *   'fragment' => '',             // the URI fragment component
     * );
     * </code>
     *
     * The returned array is similar to PHP's parse_url return value with the following
     * differences:
     *
     * <ul>
     * <li>All components are always present in the returned array</li>
     * <li>Empty and undefined component are treated differently. And empty component is
     *   set to the empty string while an undefined component is set to the `null` value.</li>
     * <li>The path component is never undefined</li>
     * <li>The method parses the URI following the RFC3986 rules but you are still
     *   required to validate the returned components against its related scheme specific rules.</li>
     * </ul>
     *
     * @link https://tools.ietf.org/html/rfc3986
     *
     * @param mixed $uri any scalar or stringable object
     *
     * @throws SyntaxError if the URI contains invalid characters
     * @throws SyntaxError if the URI contains an invalid scheme
     * @throws SyntaxError if the URI contains an invalid path
     *
     * @return array{
     *                scheme:?string,
     *                user:?string,
     *                pass:?string,
     *                host:?string,
     *                port:?int,
     *                path:string,
     *                query:?string,
     *                fragment:?string
     *                }
     */
    public static function parse($uri): array
    {
        if (is_object($uri) && method_exists($uri, '__toString')) {
            $uri = (string) $uri;
        }

        if (!is_scalar($uri)) {
            throw new \TypeError(sprintf('The uri must be a scalar or a stringable object `%s` given', gettype($uri)));
        }

        $uri = (string) $uri;

        if (isset(self::URI_SCHORTCUTS[$uri])) {
            /** @var array{scheme:?string, user:?string, pass:?string, host:?string, port:?int, path:string, query:?string, fragment:?string} $components */
            $components = array_merge(self::URI_COMPONENTS, self::URI_SCHORTCUTS[$uri]);

            return $components;
        }

        if (1 === preg_match(self::REGEXP_INVALID_URI_CHARS, $uri)) {
            throw new SyntaxError(sprintf('The uri `%s` contains invalid characters', $uri));
        }

        //if the first character is a known URI delimiter parsing can be simplified
        $first_char = $uri[0];

        //The URI is made of the fragment only
        if ('#' === $first_char) {
            [, $fragment] = explode('#', $uri, 2);
            $components = self::URI_COMPONENTS;
            $components['fragment'] = $fragment;

            return $components;
        }

        //The URI is made of the query and fragment
        if ('?' === $first_char) {
            [, $partial] = explode('?', $uri, 2);
            [$query, $fragment] = explode('#', $partial, 2) + [1 => null];
            $components = self::URI_COMPONENTS;
            $components['query'] = $query;
            $components['fragment'] = $fragment;

            return $components;
        }

        //use RFC3986 URI regexp to split the URI
        preg_match(self::REGEXP_URI_PARTS, $uri, $parts);
        $parts += ['query' => '', 'fragment' => ''];

        if (':' === $parts['scheme'] || 1 !== preg_match(self::REGEXP_URI_SCHEME, $parts['scontent'])) {
            throw new SyntaxError(sprintf('The uri `%s` contains an invalid scheme', $uri));
        }

        if ('' === $parts['scheme'].$parts['authority'] && 1 === preg_match(self::REGEXP_INVALID_PATH, $parts['path'])) {
            throw new SyntaxError(sprintf('The uri `%s` contains an invalid path.', $uri));
        }

        /** @var array{scheme:?string, user:?string, pass:?string, host:?string, port:?int, path:string, query:?string, fragment:?string} $components */
        $components = array_merge(
            self::URI_COMPONENTS,
            '' === $parts['authority'] ? [] : self::parseAuthority($parts['acontent']),
            [
                'path' => $parts['path'],
                'scheme' => '' === $parts['scheme'] ? null : $parts['scontent'],
                'query' => '' === $parts['query'] ? null : $parts['qcontent'],
                'fragment' => '' === $parts['fragment'] ? null : $parts['fcontent'],
            ]
        );

        return $components;
    }

    /**
     * Parses the URI authority part.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2
     *
     * @throws SyntaxError If the port component is invalid
     *
     * @return array{user:?string, pass:?string, host:?string, port:?int}
     */
    private static function parseAuthority(string $authority): array
    {
        $components = ['user' => null, 'pass' => null, 'host' => '', 'port' => null];
        if ('' === $authority) {
            return $components;
        }

        $parts = explode('@', $authority, 2);
        if (isset($parts[1])) {
            [$components['user'], $components['pass']] = explode(':', $parts[0], 2) + [1 => null];
        }

        preg_match(self::REGEXP_HOST_PORT, $parts[1] ?? $parts[0], $matches);
        $matches += ['port' => ''];

        $components['port'] = self::filterPort($matches['port']);
        $components['host'] = self::filterHost($matches['host']);

        return $components;
    }

    /**
     * Filter and format the port component.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @throws SyntaxError if the registered name is invalid
     */
    private static function filterPort(string $port): ?int
    {
        if ('' === $port) {
            return null;
        }

        if (1 === preg_match('/^\d*$/', $port)) {
            return (int) $port;
        }

        throw new SyntaxError(sprintf('The port `%s` is invalid', $port));
    }

    /**
     * Returns whether a hostname is valid.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @throws SyntaxError if the registered name is invalid
     */
    private static function filterHost(string $host): string
    {
        if ('' === $host) {
            return $host;
        }

        if ('[' !== $host[0] || ']' !== substr($host, -1)) {
            return self::filterRegisteredName($host);
        }

        if (!self::isIpHost(substr($host, 1, -1))) {
            throw new SyntaxError(sprintf('Host `%s` is invalid : the IP host is malformed', $host));
        }

        return $host;
    }

    /**
     * Returns whether the host is an IPv4 or a registered named.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @throws SyntaxError       if the registered name is invalid
     * @throws IdnSupportMissing if IDN support or ICU requirement are not available or met.
     */
    private static function filterRegisteredName(string $host): string
    {
        // @codeCoverageIgnoreStart
        // added because it is not possible in travis to disabled the ext/intl extension
        // see travis issue https://github.com/travis-ci/travis-ci/issues/4701
        static $idn_support = null;
        $idn_support = $idn_support ?? function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46');
        // @codeCoverageIgnoreEnd

        $formatted_host = rawurldecode($host);
        if (1 === preg_match(self::REGEXP_REGISTERED_NAME, $formatted_host)) {
            if (false === strpos($formatted_host, 'xn--')) {
                return $host;
            }

            // @codeCoverageIgnoreStart
            if (!$idn_support) {
                throw new IdnSupportMissing(sprintf('the host `%s` could not be processed for IDN. Verify that ext/intl is installed for IDN support and that ICU is at least version 4.6.', $host));
            }
            // @codeCoverageIgnoreEnd

            $unicode = idn_to_utf8($host, 0, INTL_IDNA_VARIANT_UTS46, $arr);
            if (0 !== $arr['errors']) {
                throw new SyntaxError(sprintf('The host `%s` is invalid : %s', $host, self::getIDNAErrors($arr['errors'])));
            }

            // @codeCoverageIgnoreStart
            if (false === $unicode) {
                throw new IdnSupportMissing(sprintf('The Intl extension is misconfigured for %s, please correct this issue before proceeding.', PHP_OS));
            }
            // @codeCoverageIgnoreEnd

            return $host;
        }

        //to test IDN host non-ascii characters must be present in the host
        if (1 !== preg_match(self::REGEXP_IDN_PATTERN, $formatted_host)) {
            throw new SyntaxError(sprintf('Host `%s` is invalid : the host is not a valid registered name', $host));
        }

        // @codeCoverageIgnoreStart
        if (!$idn_support) {
            throw new IdnSupportMissing(sprintf('the host `%s` could not be processed for IDN. Verify that ext/intl is installed for IDN support and that ICU is at least version 4.6.', $host));
        }
        // @codeCoverageIgnoreEnd

        $retval = idn_to_ascii($formatted_host, 0, INTL_IDNA_VARIANT_UTS46, $arr);

        if ([] === $arr) {
            throw new SyntaxError(sprintf('Host `%s` is not a valid IDN host', $host));
        }

        if (0 !== $arr['errors']) {
            throw new SyntaxError(sprintf('Host `%s` is not a valid IDN host : %s', $host, self::getIDNAErrors($arr['errors'])));
        }

        // @codeCoverageIgnoreStart
        if (false === $retval) {
            throw new IdnSupportMissing(sprintf('The Intl extension is misconfigured for %s, please correct this issue before proceeding.', PHP_OS));
        }
        // @codeCoverageIgnoreEnd

        if (false !== strpos($retval, '%')) {
            throw new SyntaxError(sprintf('Host `%s` is invalid : the host is not a valid registered name', $host));
        }

        return $host;
    }

    /**
     * Retrieves and format IDNA conversion error message.
     *
     * @link http://icu-project.org/apiref/icu4j/com/ibm/icu/text/IDNA.Error.html
     */
    private static function getIDNAErrors(int $error_byte): string
    {
        /**
         * IDNA errors.
         */
        static $idn_errors = [
            IDNA_ERROR_EMPTY_LABEL => 'a non-final domain name label (or the whole domain name) is empty',
            IDNA_ERROR_LABEL_TOO_LONG => 'a domain name label is longer than 63 bytes',
            IDNA_ERROR_DOMAIN_NAME_TOO_LONG => 'a domain name is longer than 255 bytes in its storage form',
            IDNA_ERROR_LEADING_HYPHEN => 'a label starts with a hyphen-minus ("-")',
            IDNA_ERROR_TRAILING_HYPHEN => 'a label ends with a hyphen-minus ("-")',
            IDNA_ERROR_HYPHEN_3_4 => 'a label contains hyphen-minus ("-") in the third and fourth positions',
            IDNA_ERROR_LEADING_COMBINING_MARK => 'a label starts with a combining mark',
            IDNA_ERROR_DISALLOWED => 'a label or domain name contains disallowed characters',
            IDNA_ERROR_PUNYCODE => 'a label starts with "xn--" but does not contain valid Punycode',
            IDNA_ERROR_LABEL_HAS_DOT => 'a label contains a dot=full stop',
            IDNA_ERROR_INVALID_ACE_LABEL => 'An ACE label does not contain a valid label string',
            IDNA_ERROR_BIDI => 'a label does not meet the IDNA BiDi requirements (for right-to-left characters)',
            IDNA_ERROR_CONTEXTJ => 'a label does not meet the IDNA CONTEXTJ requirements',
        ];

        $res = [];
        foreach ($idn_errors as $error => $reason) {
            if ($error === ($error_byte & $error)) {
                $res[] = $reason;
            }
        }

        return [] === $res ? 'Unknown IDNA conversion error.' : implode(', ', $res).'.';
    }

    /**
     * Validates a IPv6/IPvfuture host.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     * @link https://tools.ietf.org/html/rfc6874#section-2
     * @link https://tools.ietf.org/html/rfc6874#section-4
     */
    private static function isIpHost(string $ip_host): bool
    {
        if (false !== filter_var($ip_host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        if (1 === preg_match(self::REGEXP_IP_FUTURE, $ip_host, $matches)) {
            return !in_array($matches['version'], ['4', '6'], true);
        }

        $pos = strpos($ip_host, '%');
        if (false === $pos || 1 === preg_match(
            self::REGEXP_INVALID_HOST_CHARS,
            rawurldecode(substr($ip_host, $pos))
        )) {
            return false;
        }

        $ip_host = substr($ip_host, 0, $pos);

        return false !== filter_var($ip_host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            && 0 === strpos((string) inet_pton($ip_host), self::ZONE_ID_ADDRESS_BLOCK);
    }
}
