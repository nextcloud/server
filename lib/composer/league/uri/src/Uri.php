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

use League\Uri\Contracts\UriInterface;
use League\Uri\Exceptions\FileinfoSupportMissing;
use League\Uri\Exceptions\IdnSupportMissing;
use League\Uri\Exceptions\SyntaxError;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use function array_filter;
use function array_map;
use function base64_decode;
use function base64_encode;
use function count;
use function defined;
use function explode;
use function file_get_contents;
use function filter_var;
use function function_exists;
use function idn_to_ascii;
use function implode;
use function in_array;
use function inet_pton;
use function is_object;
use function is_scalar;
use function method_exists;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function rawurlencode;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function strspn;
use function strtolower;
use function substr;
use const FILEINFO_MIME;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_IP;
use const IDNA_CHECK_BIDI;
use const IDNA_CHECK_CONTEXTJ;
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
use const IDNA_NONTRANSITIONAL_TO_ASCII;
use const IDNA_NONTRANSITIONAL_TO_UNICODE;
use const INTL_IDNA_VARIANT_UTS46;

final class Uri implements UriInterface
{
    /**
     * RFC3986 invalid characters.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-2.2
     *
     * @var string
     */
    private const REGEXP_INVALID_CHARS = '/[\x00-\x1f\x7f]/';

    /**
     * RFC3986 Sub delimiter characters regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-2.2
     *
     * @var string
     */
    private const REGEXP_CHARS_SUBDELIM = "\!\$&'\(\)\*\+,;\=%";

    /**
     * RFC3986 unreserved characters regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-2.3
     *
     * @var string
     */
    private const REGEXP_CHARS_UNRESERVED = 'A-Za-z0-9_\-\.~';

    /**
     * RFC3986 schema regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.1
     */
    private const REGEXP_SCHEME = ',^[a-z]([-a-z0-9+.]+)?$,i';

    /**
     * RFC3986 host identified by a registered name regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     */
    private const REGEXP_HOST_REGNAME = '/^(
        (?<unreserved>[a-z0-9_~\-\.])|
        (?<sub_delims>[!$&\'()*+,;=])|
        (?<encoded>%[A-F0-9]{2})
    )+$/x';

    /**
     * RFC3986 delimiters of the generic URI components regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-2.2
     */
    private const REGEXP_HOST_GEN_DELIMS = '/[:\/?#\[\]@ ]/'; // Also includes space.

    /**
     * RFC3986 IPvFuture regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     */
    private const REGEXP_HOST_IPFUTURE = '/^
        v(?<version>[A-F0-9])+\.
        (?:
            (?<unreserved>[a-z0-9_~\-\.])|
            (?<sub_delims>[!$&\'()*+,;=:])  # also include the : character
        )+
    $/ix';

    /**
     * Significant 10 bits of IP to detect Zone ID regular expression pattern.
     */
    private const HOST_ADDRESS_BLOCK = "\xfe\x80";

    /**
     * Regular expression pattern to for file URI.
     * <volume> contains the volume but not the volume separator.
     * The volume separator may be URL-encoded (`|` as `%7C`) by ::formatPath(),
     * so we account for that here.
     */
    private const REGEXP_FILE_PATH = ',^(?<delim>/)?(?<volume>[a-zA-Z])(?:[:|\|]|%7C)(?<rest>.*)?,';

    /**
     * Mimetype regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc2397
     */
    private const REGEXP_MIMETYPE = ',^\w+/[-.\w]+(?:\+[-.\w]+)?$,';

    /**
     * Base64 content regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc2397
     */
    private const REGEXP_BINARY = ',(;|^)base64$,';

    /**
     * Windows file path string regular expression pattern.
     * <root> contains both the volume and volume separator.
     */
    private const REGEXP_WINDOW_PATH = ',^(?<root>[a-zA-Z][:|\|]),';


    /**
     * Supported schemes and corresponding default port.
     *
     * @var array
     */
    private const SCHEME_DEFAULT_PORT = [
        'data' => null,
        'file' => null,
        'ftp' => 21,
        'gopher' => 70,
        'http' => 80,
        'https' => 443,
        'ws' => 80,
        'wss' => 443,
    ];

    /**
     * URI validation methods per scheme.
     *
     * @var array
     */
    private const SCHEME_VALIDATION_METHOD = [
        'data' => 'isUriWithSchemeAndPathOnly',
        'file' => 'isUriWithSchemeHostAndPathOnly',
        'ftp' => 'isNonEmptyHostUriWithoutFragmentAndQuery',
        'gopher' => 'isNonEmptyHostUriWithoutFragmentAndQuery',
        'http' => 'isNonEmptyHostUri',
        'https' => 'isNonEmptyHostUri',
        'ws' => 'isNonEmptyHostUriWithoutFragment',
        'wss' => 'isNonEmptyHostUriWithoutFragment',
    ];

    /**
     * All ASCII letters sorted by typical frequency of occurrence.
     *
     * @var string
     */
    private const ASCII = "\x20\x65\x69\x61\x73\x6E\x74\x72\x6F\x6C\x75\x64\x5D\x5B\x63\x6D\x70\x27\x0A\x67\x7C\x68\x76\x2E\x66\x62\x2C\x3A\x3D\x2D\x71\x31\x30\x43\x32\x2A\x79\x78\x29\x28\x4C\x39\x41\x53\x2F\x50\x22\x45\x6A\x4D\x49\x6B\x33\x3E\x35\x54\x3C\x44\x34\x7D\x42\x7B\x38\x46\x77\x52\x36\x37\x55\x47\x4E\x3B\x4A\x7A\x56\x23\x48\x4F\x57\x5F\x26\x21\x4B\x3F\x58\x51\x25\x59\x5C\x09\x5A\x2B\x7E\x5E\x24\x40\x60\x7F\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";

    /**
     * URI scheme component.
     *
     * @var string|null
     */
    private $scheme;

    /**
     * URI user info part.
     *
     * @var string|null
     */
    private $user_info;

    /**
     * URI host component.
     *
     * @var string|null
     */
    private $host;

    /**
     * URI port component.
     *
     * @var int|null
     */
    private $port;

    /**
     * URI authority string representation.
     *
     * @var string|null
     */
    private $authority;

    /**
     * URI path component.
     *
     * @var string
     */
    private $path = '';

    /**
     * URI query component.
     *
     * @var string|null
     */
    private $query;

    /**
     * URI fragment component.
     *
     * @var string|null
     */
    private $fragment;

    /**
     * URI string representation.
     *
     * @var string|null
     */
    private $uri;

    /**
     * Create a new instance.
     *
     * @param ?string $scheme
     * @param ?string $user
     * @param ?string $pass
     * @param ?string $host
     * @param ?int    $port
     * @param ?string $query
     * @param ?string $fragment
     */
    private function __construct(
        ?string $scheme,
        ?string $user,
        ?string $pass,
        ?string $host,
        ?int $port,
        string $path,
        ?string $query,
        ?string $fragment
    ) {
        $this->scheme = $this->formatScheme($scheme);
        $this->user_info = $this->formatUserInfo($user, $pass);
        $this->host = $this->formatHost($host);
        $this->port = $this->formatPort($port);
        $this->authority = $this->setAuthority();
        $this->path = $this->formatPath($path);
        $this->query = $this->formatQueryAndFragment($query);
        $this->fragment = $this->formatQueryAndFragment($fragment);
        $this->assertValidState();
    }

    /**
     * Format the Scheme and Host component.
     *
     * @param ?string $scheme
     *
     * @throws SyntaxError if the scheme is invalid
     */
    private function formatScheme(?string $scheme): ?string
    {
        if (null === $scheme) {
            return $scheme;
        }

        $formatted_scheme = strtolower($scheme);
        if (1 === preg_match(self::REGEXP_SCHEME, $formatted_scheme)) {
            return $formatted_scheme;
        }

        throw new SyntaxError(sprintf('The scheme `%s` is invalid.', $scheme));
    }

    /**
     * Set the UserInfo component.
     *
     * @param ?string $user
     * @param ?string $password
     */
    private function formatUserInfo(?string $user, ?string $password): ?string
    {
        if (null === $user) {
            return $user;
        }

        static $user_pattern = '/(?:[^%'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.']++|%(?![A-Fa-f0-9]{2}))/';
        $user = preg_replace_callback($user_pattern, [Uri::class, 'urlEncodeMatch'], $user);
        if (null === $password) {
            return $user;
        }

        static $password_pattern = '/(?:[^%:'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.']++|%(?![A-Fa-f0-9]{2}))/';

        return $user.':'.preg_replace_callback($password_pattern, [Uri::class, 'urlEncodeMatch'], $password);
    }

    /**
     * Returns the RFC3986 encoded string matched.
     */
    private static function urlEncodeMatch(array $matches): string
    {
        return rawurlencode($matches[0]);
    }

    /**
     * Validate and Format the Host component.
     *
     * @param ?string $host
     */
    private function formatHost(?string $host): ?string
    {
        if (null === $host || '' === $host) {
            return $host;
        }

        if ('[' !== $host[0]) {
            return $this->formatRegisteredName($host);
        }

        return $this->formatIp($host);
    }

    /**
     * Validate and format a registered name.
     *
     * The host is converted to its ascii representation if needed
     *
     * @throws IdnSupportMissing if the submitted host required missing or misconfigured IDN support
     * @throws SyntaxError       if the submitted host is not a valid registered name
     */
    private function formatRegisteredName(string $host): string
    {
        // @codeCoverageIgnoreStart
        // added because it is not possible in travis to disabled the ext/intl extension
        // see travis issue https://github.com/travis-ci/travis-ci/issues/4701
        static $idn_support = null;
        $idn_support = $idn_support ?? function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46');
        // @codeCoverageIgnoreEnd

        $formatted_host = rawurldecode($host);
        if (1 === preg_match(self::REGEXP_HOST_REGNAME, $formatted_host)) {
            $formatted_host = strtolower($formatted_host);
            if (false === strpos($formatted_host, 'xn--')) {
                return $formatted_host;
            }

            // @codeCoverageIgnoreStart
            if (!$idn_support) {
                throw new IdnSupportMissing(sprintf('the host `%s` could not be processed for IDN. Verify that ext/intl is installed for IDN support and that ICU is at least version 4.6.', $host));
            }
            // @codeCoverageIgnoreEnd

            $unicode = idn_to_utf8(
                $host,
                IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ | IDNA_NONTRANSITIONAL_TO_UNICODE,
                INTL_IDNA_VARIANT_UTS46,
                $arr
            );

            if (0 !== $arr['errors']) {
                throw new SyntaxError(sprintf('The host `%s` is invalid : %s', $host, $this->getIDNAErrors($arr['errors'])));
            }

            // @codeCoverageIgnoreStart
            if (false === $unicode) {
                throw new IdnSupportMissing(sprintf('The Intl extension is misconfigured for %s, please correct this issue before proceeding.', PHP_OS));
            }
            // @codeCoverageIgnoreEnd

            return $formatted_host;
        }

        if (1 === preg_match(self::REGEXP_HOST_GEN_DELIMS, $formatted_host)) {
            throw new SyntaxError(sprintf('The host `%s` is invalid : a registered name can not contain URI delimiters or spaces', $host));
        }

        // @codeCoverageIgnoreStart
        if (!$idn_support) {
            throw new IdnSupportMissing(sprintf('the host `%s` could not be processed for IDN. Verify that ext/intl is installed for IDN support and that ICU is at least version 4.6.', $host));
        }
        // @codeCoverageIgnoreEnd

        $formatted_host = idn_to_ascii(
            $formatted_host,
            IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ | IDNA_NONTRANSITIONAL_TO_ASCII,
            INTL_IDNA_VARIANT_UTS46,
            $arr
        );

        if ([] === $arr) {
            throw new SyntaxError(sprintf('Host `%s` is invalid', $host));
        }

        if (0 !== $arr['errors']) {
            throw new SyntaxError(sprintf('The host `%s` is invalid : %s', $host, $this->getIDNAErrors($arr['errors'])));
        }

        // @codeCoverageIgnoreStart
        if (false === $formatted_host) {
            throw new IdnSupportMissing(sprintf('The Intl extension is misconfigured for %s, please correct this issue before proceeding.', PHP_OS));
        }
        // @codeCoverageIgnoreEnd

        return $arr['result'];
    }

    /**
     * Retrieves and format IDNA conversion error message.
     *
     * @link http://icu-project.org/apiref/icu4j/com/ibm/icu/text/IDNA.Error.html
     */
    private function getIDNAErrors(int $error_byte): string
    {
        /**
         * IDNA errors.
         */
        static $idnErrors = [
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
        foreach ($idnErrors as $error => $reason) {
            if ($error === ($error_byte & $error)) {
                $res[] = $reason;
            }
        }

        return [] === $res ? 'Unknown IDNA conversion error.' : implode(', ', $res).'.';
    }

    /**
     * Validate and Format the IPv6/IPvfuture host.
     *
     * @throws SyntaxError if the submitted host is not a valid IP host
     */
    private function formatIp(string $host): string
    {
        $ip = substr($host, 1, -1);
        if (false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $host;
        }

        if (1 === preg_match(self::REGEXP_HOST_IPFUTURE, $ip, $matches) && !in_array($matches['version'], ['4', '6'], true)) {
            return $host;
        }

        $pos = strpos($ip, '%');
        if (false === $pos) {
            throw new SyntaxError(sprintf('The host `%s` is invalid : the IP host is malformed', $host));
        }

        if (1 === preg_match(self::REGEXP_HOST_GEN_DELIMS, rawurldecode(substr($ip, $pos)))) {
            throw new SyntaxError(sprintf('The host `%s` is invalid : the IP host is malformed', $host));
        }

        $ip = substr($ip, 0, $pos);
        if (false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new SyntaxError(sprintf('The host `%s` is invalid : the IP host is malformed', $host));
        }

        //Only the address block fe80::/10 can have a Zone ID attach to
        //let's detect the link local significant 10 bits
        if (0 === strpos((string) inet_pton($ip), self::HOST_ADDRESS_BLOCK)) {
            return $host;
        }

        throw new SyntaxError(sprintf('The host `%s` is invalid : the IP host is malformed', $host));
    }

    /**
     * Format the Port component.
     *
     * @param null|mixed $port
     *
     * @throws SyntaxError
     */
    private function formatPort($port = null): ?int
    {
        if (null === $port || '' === $port) {
            return null;
        }

        if (!is_int($port) && !(is_string($port) && 1 === preg_match('/^\d*$/', $port))) {
            throw new SyntaxError(sprintf('The port `%s` is invalid', $port));
        }

        $port = (int) $port;
        if (0 > $port) {
            throw new SyntaxError(sprintf('The port `%s` is invalid', $port));
        }

        $defaultPort = self::SCHEME_DEFAULT_PORT[$this->scheme] ?? null;
        if ($defaultPort === $port) {
            return null;
        }

        return $port;
    }

    /**
     * {@inheritDoc}
     */
    public static function __set_state(array $components): self
    {
        $components['user'] = null;
        $components['pass'] = null;
        if (null !== $components['user_info']) {
            [$components['user'], $components['pass']] = explode(':', $components['user_info'], 2) + [1 => null];
        }

        return new self(
            $components['scheme'],
            $components['user'],
            $components['pass'],
            $components['host'],
            $components['port'],
            $components['path'],
            $components['query'],
            $components['fragment']
        );
    }

    /**
     * Create a new instance from a URI and a Base URI.
     *
     * The returned URI must be absolute.
     *
     * @param mixed      $uri      the input URI to create
     * @param null|mixed $base_uri the base URI used for reference
     */
    public static function createFromBaseUri($uri, $base_uri = null): UriInterface
    {
        if (!$uri instanceof UriInterface) {
            $uri = self::createFromString($uri);
        }

        if (null === $base_uri) {
            if (null === $uri->getScheme()) {
                throw new SyntaxError(sprintf('the URI `%s` must be absolute', (string) $uri));
            }

            if (null === $uri->getAuthority()) {
                return $uri;
            }

            /** @var UriInterface $uri */
            $uri = UriResolver::resolve($uri, $uri->withFragment(null)->withQuery(null)->withPath(''));

            return $uri;
        }

        if (!$base_uri instanceof UriInterface) {
            $base_uri = self::createFromString($base_uri);
        }

        if (null === $base_uri->getScheme()) {
            throw new SyntaxError(sprintf('the base URI `%s` must be absolute', (string) $base_uri));
        }

        /** @var UriInterface $uri */
        $uri = UriResolver::resolve($uri, $base_uri);

        return $uri;
    }

    /**
     * Create a new instance from a string.
     *
     * @param string|mixed $uri
     */
    public static function createFromString($uri = ''): self
    {
        $components = UriString::parse($uri);

        return new self(
            $components['scheme'],
            $components['user'],
            $components['pass'],
            $components['host'],
            $components['port'],
            $components['path'],
            $components['query'],
            $components['fragment']
        );
    }

    /**
     * Create a new instance from a hash of parse_url parts.
     *
     * Create an new instance from a hash representation of the URI similar
     * to PHP parse_url function result
     *
     * @param array<string, mixed> $components
     */
    public static function createFromComponents(array $components = []): self
    {
        $components += [
            'scheme' => null, 'user' => null, 'pass' => null, 'host' => null,
            'port' => null, 'path' => '', 'query' => null, 'fragment' => null,
        ];

        return new self(
            $components['scheme'],
            $components['user'],
            $components['pass'],
            $components['host'],
            $components['port'],
            $components['path'],
            $components['query'],
            $components['fragment']
        );
    }

    /**
     * Create a new instance from a data file path.
     *
     * @param resource|null $context
     *
     * @throws FileinfoSupportMissing If ext/fileinfo is not installed
     * @throws SyntaxError            If the file does not exist or is not readable
     */
    public static function createFromDataPath(string $path, $context = null): self
    {
        static $finfo_support = null;
        $finfo_support = $finfo_support ?? class_exists(\finfo::class);

        // @codeCoverageIgnoreStart
        if (!$finfo_support) {
            throw new FileinfoSupportMissing(sprintf('Please install ext/fileinfo to use the %s() method.', __METHOD__));
        }
        // @codeCoverageIgnoreEnd

        $file_args = [$path, false];
        $mime_args = [$path, FILEINFO_MIME];
        if (null !== $context) {
            $file_args[] = $context;
            $mime_args[] = $context;
        }

        $raw = @file_get_contents(...$file_args);
        if (false === $raw) {
            throw new SyntaxError(sprintf('The file `%s` does not exist or is not readable', $path));
        }

        $mimetype = (string) (new \finfo(FILEINFO_MIME))->file(...$mime_args);

        return Uri::createFromComponents([
            'scheme' => 'data',
            'path' => str_replace(' ', '', $mimetype.';base64,'.base64_encode($raw)),
        ]);
    }

    /**
     * Create a new instance from a Unix path string.
     */
    public static function createFromUnixPath(string $uri = ''): self
    {
        $uri = implode('/', array_map('rawurlencode', explode('/', $uri)));
        if ('/' !== ($uri[0] ?? '')) {
            return Uri::createFromComponents(['path' => $uri]);
        }

        return Uri::createFromComponents(['path' => $uri, 'scheme' => 'file', 'host' => '']);
    }

    /**
     * Create a new instance from a local Windows path string.
     */
    public static function createFromWindowsPath(string $uri = ''): self
    {
        $root = '';
        if (1 === preg_match(self::REGEXP_WINDOW_PATH, $uri, $matches)) {
            $root = substr($matches['root'], 0, -1).':';
            $uri = substr($uri, strlen($root));
        }
        $uri = str_replace('\\', '/', $uri);
        $uri = implode('/', array_map('rawurlencode', explode('/', $uri)));

        //Local Windows absolute path
        if ('' !== $root) {
            return Uri::createFromComponents(['path' => '/'.$root.$uri, 'scheme' => 'file', 'host' => '']);
        }

        //UNC Windows Path
        if ('//' !== substr($uri, 0, 2)) {
            return Uri::createFromComponents(['path' => $uri]);
        }

        $parts = explode('/', substr($uri, 2), 2) + [1 => null];

        return Uri::createFromComponents(['host' => $parts[0], 'path' => '/'.$parts[1], 'scheme' => 'file']);
    }

    /**
     * Create a new instance from a URI object.
     *
     * @param Psr7UriInterface|UriInterface $uri the input URI to create
     */
    public static function createFromUri($uri): self
    {
        if ($uri instanceof UriInterface) {
            $user_info = $uri->getUserInfo();
            $user = null;
            $pass = null;
            if (null !== $user_info) {
                [$user, $pass] = explode(':', $user_info, 2) + [1 => null];
            }

            return new self(
                $uri->getScheme(),
                $user,
                $pass,
                $uri->getHost(),
                $uri->getPort(),
                $uri->getPath(),
                $uri->getQuery(),
                $uri->getFragment()
            );
        }

        if (!$uri instanceof Psr7UriInterface) {
            throw new \TypeError(sprintf('The object must implement the `%s` or the `%s`', Psr7UriInterface::class, UriInterface::class));
        }

        $scheme = $uri->getScheme();
        if ('' === $scheme) {
            $scheme = null;
        }

        $fragment = $uri->getFragment();
        if ('' === $fragment) {
            $fragment = null;
        }

        $query = $uri->getQuery();
        if ('' === $query) {
            $query = null;
        }

        $host = $uri->getHost();
        if ('' === $host) {
            $host = null;
        }

        $user_info = $uri->getUserInfo();
        $user = null;
        $pass = null;
        if ('' !== $user_info) {
            [$user, $pass] = explode(':', $user_info, 2) + [1 => null];
        }

        return new self(
            $scheme,
            $user,
            $pass,
            $host,
            $uri->getPort(),
            $uri->getPath(),
            $query,
            $fragment
        );
    }

    /**
     * Create a new instance from the environment.
     */
    public static function createFromServer(array $server): self
    {
        [$user, $pass] = self::fetchUserInfo($server);
        [$host, $port] = self::fetchHostname($server);
        [$path, $query] = self::fetchRequestUri($server);

        return Uri::createFromComponents([
            'scheme' => self::fetchScheme($server),
            'user' => $user,
            'pass' => $pass,
            'host' => $host,
            'port' => $port,
            'path' => $path,
            'query' => $query,
        ]);
    }

    /**
     * Returns the environment scheme.
     */
    private static function fetchScheme(array $server): string
    {
        $server += ['HTTPS' => ''];
        $res = filter_var($server['HTTPS'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return false !== $res ? 'https' : 'http';
    }

    /**
     * Returns the environment user info.
     *
     * @return array{0:?string, 1:?string}
     */
    private static function fetchUserInfo(array $server): array
    {
        $server += ['PHP_AUTH_USER' => null, 'PHP_AUTH_PW' => null, 'HTTP_AUTHORIZATION' => ''];
        $user = $server['PHP_AUTH_USER'];
        $pass = $server['PHP_AUTH_PW'];
        if (0 === strpos(strtolower($server['HTTP_AUTHORIZATION']), 'basic')) {
            $userinfo = base64_decode(substr($server['HTTP_AUTHORIZATION'], 6), true);
            if (false === $userinfo) {
                throw new SyntaxError('The user info could not be detected');
            }
            [$user, $pass] = explode(':', $userinfo, 2) + [1 => null];
        }

        if (null !== $user) {
            $user = rawurlencode($user);
        }

        if (null !== $pass) {
            $pass = rawurlencode($pass);
        }

        return [$user, $pass];
    }

    /**
     * Returns the environment host.
     *
     * @throws SyntaxError If the host can not be detected
     *
     * @return array{0:?string, 1:?string}
     */
    private static function fetchHostname(array $server): array
    {
        $server += ['SERVER_PORT' => null];
        if (null !== $server['SERVER_PORT']) {
            $server['SERVER_PORT'] = (int) $server['SERVER_PORT'];
        }

        if (isset($server['HTTP_HOST'])) {
            preg_match(',^(?<host>(\[.*]|[^:])*)(:(?<port>[^/?#]*))?$,x', $server['HTTP_HOST'], $matches);

            return [
                $matches['host'],
                isset($matches['port']) ? (int) $matches['port'] : $server['SERVER_PORT'],
            ];
        }

        if (!isset($server['SERVER_ADDR'])) {
            throw new SyntaxError('The host could not be detected');
        }

        if (false === filter_var($server['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $server['SERVER_ADDR'] = '['.$server['SERVER_ADDR'].']';
        }

        return [$server['SERVER_ADDR'], $server['SERVER_PORT']];
    }

    /**
     * Returns the environment path.
     *
     * @return array{0:?string, 1:?string}
     */
    private static function fetchRequestUri(array $server): array
    {
        $server += ['IIS_WasUrlRewritten' => null, 'UNENCODED_URL' => '', 'PHP_SELF' => '', 'QUERY_STRING' => null];
        if ('1' === $server['IIS_WasUrlRewritten'] && '' !== $server['UNENCODED_URL']) {
            /** @var array{0:?string, 1:?string} $retval */
            $retval = explode('?', $server['UNENCODED_URL'], 2) + [1 => null];

            return $retval;
        }

        if (isset($server['REQUEST_URI'])) {
            [$path, ] = explode('?', $server['REQUEST_URI'], 2);
            $query = ('' !== $server['QUERY_STRING']) ? $server['QUERY_STRING'] : null;

            return [$path, $query];
        }

        return [$server['PHP_SELF'], $server['QUERY_STRING']];
    }

    /**
     * Generate the URI authority part.
     */
    private function setAuthority(): ?string
    {
        $authority = null;
        if (null !== $this->user_info) {
            $authority = $this->user_info.'@';
        }

        if (null !== $this->host) {
            $authority .= $this->host;
        }

        if (null !== $this->port) {
            $authority .= ':'.$this->port;
        }

        return $authority;
    }

    /**
     * Format the Path component.
     */
    private function formatPath(string $path): string
    {
        $path = $this->formatDataPath($path);

        static $pattern = '/(?:[^'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.'%:@\/}{]++|%(?![A-Fa-f0-9]{2}))/';

        $path = (string) preg_replace_callback($pattern, [Uri::class, 'urlEncodeMatch'], $path);

        return $this->formatFilePath($path);
    }

    /**
     * Filter the Path component.
     *
     * @link https://tools.ietf.org/html/rfc2397
     *
     * @throws SyntaxError If the path is not compliant with RFC2397
     */
    private function formatDataPath(string $path): string
    {
        if ('data' !== $this->scheme) {
            return $path;
        }

        if ('' == $path) {
            return 'text/plain;charset=us-ascii,';
        }

        if (strlen($path) !== strspn($path, self::ASCII) || false === strpos($path, ',')) {
            throw new SyntaxError(sprintf('The path `%s` is invalid according to RFC2937', $path));
        }

        $parts = explode(',', $path, 2) + [1 => null];
        $mediatype = explode(';', (string) $parts[0], 2) + [1 => null];
        $data = (string) $parts[1];
        $mimetype = $mediatype[0];
        if (null === $mimetype || '' === $mimetype) {
            $mimetype = 'text/plain';
        }

        $parameters = $mediatype[1];
        if (null === $parameters || '' === $parameters) {
            $parameters = 'charset=us-ascii';
        }

        $this->assertValidPath($mimetype, $parameters, $data);

        return $mimetype.';'.$parameters.','.$data;
    }

    /**
     * Assert the path is a compliant with RFC2397.
     *
     * @link https://tools.ietf.org/html/rfc2397
     *
     * @throws SyntaxError If the mediatype or the data are not compliant with the RFC2397
     */
    private function assertValidPath(string $mimetype, string $parameters, string $data): void
    {
        if (1 !== preg_match(self::REGEXP_MIMETYPE, $mimetype)) {
            throw new SyntaxError(sprintf('The path mimetype `%s` is invalid', $mimetype));
        }

        $is_binary = 1 === preg_match(self::REGEXP_BINARY, $parameters, $matches);
        if ($is_binary) {
            $parameters = substr($parameters, 0, - strlen($matches[0]));
        }

        $res = array_filter(array_filter(explode(';', $parameters), [$this, 'validateParameter']));
        if ([] !== $res) {
            throw new SyntaxError(sprintf('The path paremeters `%s` is invalid', $parameters));
        }

        if (!$is_binary) {
            return;
        }

        $res = base64_decode($data, true);
        if (false === $res || $data !== base64_encode($res)) {
            throw new SyntaxError(sprintf('The path data `%s` is invalid', $data));
        }
    }

    /**
     * Validate mediatype parameter.
     */
    private function validateParameter(string $parameter): bool
    {
        $properties = explode('=', $parameter);

        return 2 != count($properties) || 'base64' === strtolower($properties[0]);
    }

    /**
     * Format path component for file scheme.
     */
    private function formatFilePath(string $path): string
    {
        if ('file' !== $this->scheme) {
            return $path;
        }

        $replace = static function (array $matches): string {
            return $matches['delim'].$matches['volume'].':'.$matches['rest'];
        };

        return (string) preg_replace_callback(self::REGEXP_FILE_PATH, $replace, $path);
    }

    /**
     * Format the Query or the Fragment component.
     *
     * Returns a array containing:
     * <ul>
     * <li> the formatted component (a string or null)</li>
     * <li> a boolean flag telling wether the delimiter is to be added to the component
     * when building the URI string representation</li>
     * </ul>
     *
     * @param ?string $component
     */
    private function formatQueryAndFragment(?string $component): ?string
    {
        if (null === $component || '' === $component) {
            return $component;
        }

        static $pattern = '/(?:[^'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.'%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/';
        return preg_replace_callback($pattern, [Uri::class, 'urlEncodeMatch'], $component);
    }

    /**
     * assert the URI internal state is valid.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3
     * @link https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @throws SyntaxError if the URI is in an invalid state according to RFC3986
     * @throws SyntaxError if the URI is in an invalid state according to scheme specific rules
     */
    private function assertValidState(): void
    {
        if (null !== $this->authority && ('' !== $this->path && '/' !== $this->path[0])) {
            throw new SyntaxError('If an authority is present the path must be empty or start with a `/`.');
        }

        if (null === $this->authority && 0 === strpos($this->path, '//')) {
            throw new SyntaxError(sprintf('If there is no authority the path `%s` can not start with a `//`.', $this->path));
        }

        $pos = strpos($this->path, ':');
        if (null === $this->authority
            && null === $this->scheme
            && false !== $pos
            && false === strpos(substr($this->path, 0, $pos), '/')
        ) {
            throw new SyntaxError('In absence of a scheme and an authority the first path segment cannot contain a colon (":") character.');
        }

        $validationMethod = self::SCHEME_VALIDATION_METHOD[$this->scheme] ?? null;
        if (null === $validationMethod || true === $this->$validationMethod()) {
            $this->uri = null;

            return;
        }

        throw new SyntaxError(sprintf('The uri `%s` is invalid for the `%s` scheme.', (string) $this, $this->scheme));
    }

    /**
     * URI validation for URI schemes which allows only scheme and path components.
     */
    private function isUriWithSchemeAndPathOnly(): bool
    {
        return null === $this->authority
            && null === $this->query
            && null === $this->fragment;
    }

    /**
     * URI validation for URI schemes which allows only scheme, host and path components.
     */
    private function isUriWithSchemeHostAndPathOnly(): bool
    {
        return null === $this->user_info
            && null === $this->port
            && null === $this->query
            && null === $this->fragment
            && !('' != $this->scheme && null === $this->host);
    }

    /**
     * URI validation for URI schemes which disallow the empty '' host.
     */
    private function isNonEmptyHostUri(): bool
    {
        return '' !== $this->host
            && !(null !== $this->scheme && null === $this->host);
    }

    /**
     * URI validation for URIs schemes which disallow the empty '' host
     * and forbids the fragment component.
     */
    private function isNonEmptyHostUriWithoutFragment(): bool
    {
        return $this->isNonEmptyHostUri() && null === $this->fragment;
    }

    /**
     * URI validation for URIs schemes which disallow the empty '' host
     * and forbids fragment and query components.
     */
    private function isNonEmptyHostUriWithoutFragmentAndQuery(): bool
    {
        return $this->isNonEmptyHostUri() && null === $this->fragment && null === $this->query;
    }

    /**
     * Generate the URI string representation from its components.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-5.3
     *
     * @param ?string $scheme
     * @param ?string $authority
     * @param ?string $query
     * @param ?string $fragment
     */
    private function getUriString(
        ?string $scheme,
        ?string $authority,
        string $path,
        ?string $query,
        ?string $fragment
    ): string {
        if (null !== $scheme) {
            $scheme = $scheme.':';
        }

        if (null !== $authority) {
            $authority = '//'.$authority;
        }

        if (null !== $query) {
            $query = '?'.$query;
        }

        if (null !== $fragment) {
            $fragment = '#'.$fragment;
        }

        return $scheme.$authority.$path.$query.$fragment;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        $this->uri = $this->uri ?? $this->getUriString(
            $this->scheme,
            $this->authority,
            $this->path,
            $this->query,
            $this->fragment
        );

        return $this->uri;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): string
    {
        return $this->__toString();
    }

    /**
     * {@inheritDoc}
     *
     * @return array{scheme:?string, user_info:?string, host:?string, port:?int, path:string, query:?string, fragment:?string}
     */
    public function __debugInfo(): array
    {
        return [
            'scheme' => $this->scheme,
            'user_info' => isset($this->user_info) ? preg_replace(',:(.*).?$,', ':***', $this->user_info) : null,
            'host' => $this->host,
            'port' => $this->port,
            'path' => $this->path,
            'query' => $this->query,
            'fragment' => $this->fragment,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthority(): ?string
    {
        return $this->authority;
    }

    /**
     * {@inheritDoc}
     */
    public function getUserInfo(): ?string
    {
        return $this->user_info;
    }

    /**
     * {@inheritDoc}
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * {@inheritDoc}
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * {@inheritDoc}
     */
    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    /**
     * {@inheritDoc}
     */
    public function withScheme($scheme): UriInterface
    {
        $scheme = $this->formatScheme($this->filterString($scheme));
        if ($scheme === $this->scheme) {
            return $this;
        }

        $clone = clone $this;
        $clone->scheme = $scheme;
        $clone->port = $clone->formatPort($clone->port);
        $clone->authority = $clone->setAuthority();
        $clone->assertValidState();

        return $clone;
    }

    /**
     * Filter a string.
     *
     * @param mixed $str the value to evaluate as a string
     *
     * @throws SyntaxError if the submitted data can not be converted to string
     */
    private function filterString($str): ?string
    {
        if (null === $str) {
            return $str;
        }

        if (is_object($str) && method_exists($str, '__toString')) {
            $str = (string) $str;
        }

        if (!is_scalar($str)) {
            throw new \TypeError(sprintf('The component must be a string, a scalar or a stringable object %s given.', gettype($str)));
        }

        $str = (string) $str;
        if (1 !== preg_match(self::REGEXP_INVALID_CHARS, $str)) {
            return $str;
        }

        throw new SyntaxError(sprintf('The component `%s` contains invalid characters.', $str));
    }

    /**
     * {@inheritDoc}
     */
    public function withUserInfo($user, $password = null): UriInterface
    {
        $user_info = null;
        $user = $this->filterString($user);
        if (null !== $password) {
            $password = $this->filterString($password);
        }

        if ('' !== $user) {
            $user_info = $this->formatUserInfo($user, $password);
        }

        if ($user_info === $this->user_info) {
            return $this;
        }

        $clone = clone $this;
        $clone->user_info = $user_info;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidState();

        return $clone;
    }

    /**
     * {@inheritDoc}
     */
    public function withHost($host): UriInterface
    {
        $host = $this->formatHost($this->filterString($host));
        if ($host === $this->host) {
            return $this;
        }

        $clone = clone $this;
        $clone->host = $host;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidState();

        return $clone;
    }

    /**
     * {@inheritDoc}
     */
    public function withPort($port): UriInterface
    {
        $port = $this->formatPort($port);
        if ($port === $this->port) {
            return $this;
        }

        $clone = clone $this;
        $clone->port = $port;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidState();

        return $clone;
    }

    /**
     * {@inheritDoc}
     */
    public function withPath($path): UriInterface
    {
        $path = $this->filterString($path);
        if (null === $path) {
            throw new \TypeError('A path must be a string NULL given.');
        }

        $path = $this->formatPath($path);
        if ($path === $this->path) {
            return $this;
        }

        $clone = clone $this;
        $clone->path = $path;
        $clone->assertValidState();

        return $clone;
    }

    /**
     * {@inheritDoc}
     */
    public function withQuery($query): UriInterface
    {
        $query = $this->formatQueryAndFragment($this->filterString($query));
        if ($query === $this->query) {
            return $this;
        }

        $clone = clone $this;
        $clone->query = $query;
        $clone->assertValidState();

        return $clone;
    }

    /**
     * {@inheritDoc}
     */
    public function withFragment($fragment): UriInterface
    {
        $fragment = $this->formatQueryAndFragment($this->filterString($fragment));
        if ($fragment === $this->fragment) {
            return $this;
        }

        $clone = clone $this;
        $clone->fragment = $fragment;
        $clone->assertValidState();

        return $clone;
    }
}
