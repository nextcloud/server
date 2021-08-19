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

namespace League\Uri\Contracts;

use League\Uri\Exceptions\IdnSupportMissing;
use League\Uri\Exceptions\SyntaxError;

interface UriInterface extends \JsonSerializable
{
    /**
     * Returns the string representation as a URI reference.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     */
    public function __toString(): string;

    /**
     * Returns the string representation as a URI reference.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @see ::__toString
     */
    public function jsonSerialize(): string;

    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return a null value.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     */
    public function getScheme(): ?string;

    /**
     * Retrieve the authority component of the URI.
     *
     * If no scheme is present, this method MUST return a null value.
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     */
    public function getAuthority(): ?string;

    /**
     * Retrieve the user information component of the URI.
     *
     * If no scheme is present, this method MUST return a null value.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     */
    public function getUserInfo(): ?string;

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present this method MUST return a null value.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     */
    public function getHost(): ?string;

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     */
    public function getPort(): ?int;

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     */
    public function getPath(): string;

    /**
     * Retrieve the query string of the URI.
     *
     * If no host is present this method MUST return a null value.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     */
    public function getQuery(): ?string;

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no host is present this method MUST return a null value.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     */
    public function getFragment(): ?string;

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * A null value provided for the scheme is equivalent to removing the scheme
     * information.
     *
     * @param ?string $scheme
     *
     * @throws SyntaxError for invalid component or transformations
     *                     that would result in a object in invalid state.
     */
    public function withScheme(?string $scheme): self;

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; a null value for the user is equivalent to removing user
     * information.
     *
     * @param ?string $user
     * @param ?string $password
     *
     * @throws SyntaxError for invalid component or transformations
     *                     that would result in a object in invalid state.
     */
    public function withUserInfo(?string $user, ?string $password = null): self;

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * A null value provided for the host is equivalent to removing the host
     * information.
     *
     * @param ?string $host
     *
     * @throws SyntaxError       for invalid component or transformations
     *                           that would result in a object in invalid state.
     * @throws IdnSupportMissing for component or transformations
     *                           requiring IDN support when IDN support is not present
     *                           or misconfigured.
     */
    public function withHost(?string $host): self;

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param ?int $port
     *
     * @throws SyntaxError for invalid component or transformations
     *                     that would result in a object in invalid state.
     */
    public function withPort(?int $port): self;

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @throws SyntaxError for invalid component or transformations
     *                     that would result in a object in invalid state.
     */
    public function withPath(string $path): self;

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * A null value provided for the query is equivalent to removing the query
     * information.
     *
     * @param ?string $query
     *
     * @throws SyntaxError for invalid component or transformations
     *                     that would result in a object in invalid state.
     */
    public function withQuery(?string $query): self;

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * A null value provided for the fragment is equivalent to removing the fragment
     * information.
     *
     * @param ?string $fragment
     *
     * @throws SyntaxError for invalid component or transformations
     *                     that would result in a object in invalid state.
     */
    public function withFragment(?string $fragment): self;
}
