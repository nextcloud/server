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

interface UriComponentInterface extends \JsonSerializable
{
    /**
     * Returns the instance content.
     *
     * If the instance is defined, the value returned MUST be encoded according to the
     * selected encoding algorithm. In any case, the value MUST NOT double-encode any character
     * depending on the selected encoding algorithm.
     *
     * To determine what characters to encode, please refer to RFC 3986, Sections 2 and 3.
     * or RFC 3987 Section 3. By default the content is encoded according to RFC3986
     *
     * If the instance is not defined null is returned
     */
    public function getContent(): ?string;

    /**
     * Returns the instance string representation.
     *
     * If the instance is defined, the value returned MUST be percent-encoded,
     * but MUST NOT double-encode any characters. To determine what characters
     * to encode, please refer to RFC 3986, Sections 2 and 3.
     *
     * If the instance is not defined an empty string is returned
     */
    public function __toString(): string;

    /**
     * Returns the instance json representation.
     *
     * If the instance is defined, the value returned MUST be percent-encoded,
     * but MUST NOT double-encode any characters. To determine what characters
     * to encode, please refer to RFC 3986 or RFC 1738.
     *
     * If the instance is not defined null is returned
     */
    public function jsonSerialize(): ?string;

    /**
     * Returns the instance string representation with its optional URI delimiters.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode any
     * characters. To determine what characters to encode, please refer to RFC 3986,
     * Sections 2 and 3.
     *
     * If the instance is not defined an empty string is returned
     */
    public function getUriComponent(): string;

    /**
     * Returns an instance with the specified content.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified content.
     *
     * Users can provide both encoded and decoded content characters.
     *
     * A null value is equivalent to removing the component content.
     *
     *
     * @param ?string $content
     *
     * @throws SyntaxError       for invalid component or transformations
     *                           that would result in a object in invalid state.
     * @throws IdnSupportMissing for component or transformations
     *                           requiring IDN support when IDN support is not present
     *                           or misconfigured.
     */
    public function withContent(?string $content): self;
}
