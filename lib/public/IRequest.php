<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP;

/**
 * Immutable request wrapper with accessors for request variables and other
 * request-related data.
 * 
 * Request data should be retrieved through this interface whenever possible.
 *
 * Parameters can be accessed through dedicated methods or via magic property
 * access, for example:
 *
 * $request->post['myvar']; // POST body parameters on POST requests
 * $request->myvar;         // merged request parameters
 *
 * Magic access to a named parameter reads from the merged request parameter
 * set. Method-specific properties such as `get`, `post`, `put`, and `patch`
 * are only available for the matching HTTP method and may throw a
 * \LogicException otherwise.
 *
 * In PUT requests, if the body is JSON or form-encoded, `->put` behaves like
 * the other method-specific accessors and returns parsed request parameters.
 * Otherwise, for non-empty request bodies, it returns a readable stream
 * resource for the raw request body. Such streamed PUT bodies can only be
 * accessed once; repeated access throws a \LogicException.
 *
 * @property-read array<string, mixed> $get
 * @property-read array<string, mixed> $post
 * @property-read array<string, mixed>|resource $put
 * @property-read array<string, mixed> $patch
 * @property-read string $method
 * @property-read array<string, mixed> $server
 * @property-read string[] $urlParams
 *
 * @since 6.0.0
 */
interface IRequest {
	/**
	 * @since 9.1.0
	 * @since 28.0.0 The regex has a group matching the version number
	 */
	public const USER_AGENT_CLIENT_ANDROID = '/^Mozilla\/5\.0 \(Android\) (?:ownCloud|Nextcloud)\-android\/([^ ]*).*$/';

	/**
	 * @since 13.0.0
	 * @since 28.0.0 The regex has a group matching the version number
	 */
	public const USER_AGENT_TALK_ANDROID = '/^Mozilla\/5\.0 \(Android\) Nextcloud\-Talk v([^ ]*).*$/';

	/**
	 * @since 9.1.0
	 * @since 28.0.0 The regex has a group matching the version number
	 */
	public const USER_AGENT_CLIENT_DESKTOP = '/^Mozilla\/5\.0 \([A-Za-z ]+\) (?:mirall|csyncoC)\/([^ ]*).*$/';

	/**
	 * @since 26.0.0
	 * @since 28.0.0 The regex has a group matching the version number
	 */
	public const USER_AGENT_TALK_DESKTOP = '/^Mozilla\/5\.0 \((?!Android|iOS)[A-Za-z ]+\) Nextcloud\-Talk v([^ ]*).*$/';

	/**
	 * @since 9.1.0
	 * @since 28.0.0 The regex has a group matching the version number
	 */
	public const USER_AGENT_CLIENT_IOS = '/^Mozilla\/5\.0 \(iOS\) (?:ownCloud|Nextcloud)\-iOS\/([^ ]*).*$/';

	/**
	 * @since 13.0.0
	 * @since 28.0.0 The regex has a group matching the version number
	 */
	public const USER_AGENT_TALK_IOS = '/^Mozilla\/5\.0 \(iOS\) Nextcloud\-Talk v([^ ]*).*$/';

	/**
	 * @since 13.0.1
	 * @since 28.0.0 The regex has a group matching the version number
	 */
	public const USER_AGENT_OUTLOOK_ADDON = '/^Mozilla\/5\.0 \([A-Za-z ]+\) Nextcloud\-Outlook v([^ ]*).*$/';

	/**
	 * @since 13.0.1
	 * @since 28.0.0 The regex has a group matching the version number
	 */
	public const USER_AGENT_THUNDERBIRD_ADDON = '/^Filelink for \*cloud\/([1-9]\d*\.\d+\.\d+)$/';

	/**
	 * @since 26.0.0
	 */
	public const JSON_CONTENT_TYPE_REGEX = '/^application\/(?:[a-z0-9.-]+\+)?json\b/';

	/**
	 * Returns the value of a request header, or an empty string if missing.
	 *
	 * Header names are matched case-insensitively.
	 *
	 * Besides normal HTTP headers, also supports selected request-related
	 * server values such as `REMOTE_ADDR`.
	 *
	 * @psalm-taint-source input
	 *
	 * @since 6.0.0
	 */
	public function getHeader(string $name): string;

	/**
	 * Returns a parameter value from the merged parameter set.
	 *
	 * The merged parameter set is primarily composed from route URL parameters,
	 * POST parameters and GET parameters. Depending on request content type and
	 * prior access, lazily decoded request-body parameters may also be present.
	 *
	 * @psalm-taint-source input
	 *
	 * @param string $key the key to look up
	 * @param mixed $default the value to return if the key is not found
	 * @return mixed the parameter value, or $default if the key is not present
	 * @since 6.0.0
	 */
	public function getParam(string $key, $default = null);

	/**
	 * Returns the merged parameter set currently available on the request.
	 *
	 * This includes request parameters from GET, POST and route URL parameters,
	 * and may also include decoded request-body parameters.
	 *
	 * @psalm-taint-source input
	 *
	 * @return array the merged parameters
	 * @since 6.0.0
	 */
	public function getParams(): array;

	/**
	 * Returns the method of the request
	 *
	 * @return string the method of the request (POST, GET, etc)
	 * @since 6.0.0
	 */
	public function getMethod(): string;

	/**
	 * Shortcut for accessing an uploaded file through the $_FILES array
	 *
	 * @param string $key the key that will be taken from the $_FILES array
	 * @return array the file in the $_FILES element
	 * @since 6.0.0
	 */
	public function getUploadedFile(string $key);

	/**
	 * Shortcut for getting env variables
	 *
	 * @param string $key the key that will be taken from the $_ENV array
	 * @return array the value in the $_ENV element
	 * @since 6.0.0
	 */
	public function getEnv(string $key);

	/**
	 * Shortcut for getting cookie variables
	 *
	 * @psalm-taint-source input
	 *
	 * @param string $key the key that will be taken from the $_COOKIE array
	 * @return string|null the value in the $_COOKIE element
	 * @since 6.0.0
	 */
	public function getCookie(string $key);

	/**
	 * Checks if the CSRF check was correct
	 *
	 * @return bool true if CSRF check passed
	 * @since 6.0.0
	 */
	public function passesCSRFCheck(): bool;

	/**
	 * Checks if the strict cookie has been sent with the request if the request
	 * is including any cookies.
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function passesStrictCookieCheck(): bool;

	/**
	 * Checks if the lax cookie has been sent with the request if the request
	 * is including any cookies.
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function passesLaxCookieCheck(): bool;

	/**
	 * Returns an ID for the request, value is not guaranteed to be unique and is mostly meant for logging
	 * If `mod_unique_id` is installed this value will be taken.
	 *
	 * @return string
	 * @since 8.1.0
	 */
	public function getId(): string;

	/**
	 * Returns the remote address, if the connection came from a trusted proxy
	 * and `forwarded_for_headers` has been configured then the IP address
	 * specified in this header will be returned instead.
	 * Do always use this instead of $_SERVER['REMOTE_ADDR']
	 *
	 * @return string IP address
	 * @since 8.1.0
	 */
	public function getRemoteAddress(): string;

	/**
	 * Returns the server protocol. It respects one or more reverse proxies servers
	 * and load balancers. Precedence:
	 *   1. `overwriteprotocol` config value
	 *   2. `X-Forwarded-Proto` header value
	 *   3. $_SERVER['HTTPS'] value
	 * If an invalid protocol is provided, defaults to http, continues, but logs as an error.
	 *
	 * @return string Server protocol (http or https)
	 * @since 8.1.0
	 */
	public function getServerProtocol(): string;

	/**
	 * Returns the used HTTP protocol.
	 *
	 * @return string HTTP protocol. HTTP/2, HTTP/1.1 or HTTP/1.0.
	 * @since 8.2.0
	 */
	public function getHttpProtocol(): string;

	/**
	 * Returns the request uri, even if the website uses one or more
	 * reverse proxies
	 *
	 * @psalm-taint-source input
	 *
	 * @return string
	 * @since 8.1.0
	 */
	public function getRequestUri(): string;

	/**
	 * Get raw PathInfo from request (not urldecoded)
	 *
	 * @psalm-taint-source input
	 *
	 * @throws \Exception
	 * @return string Path info
	 * @since 8.1.0
	 */
	public function getRawPathInfo(): string;

	/**
	 * Get PathInfo from request (rawurldecoded)
	 *
	 * @psalm-taint-source input
	 *
	 * @throws \Exception
	 * @return string|false Path info or false when not found
	 * @since 8.1.0
	 */
	public function getPathInfo();

	/**
	 * Returns the script name, even if the website uses one or more
	 * reverse proxies
	 *
	 * @return string the script name
	 * @since 8.1.0
	 */
	public function getScriptName(): string;

	/**
	 * Checks whether the user agent matches a given regex
	 *
	 * @param array $agent array of agent names
	 * @return bool true if at least one of the given agent matches, false otherwise
	 * @since 8.1.0
	 */
	public function isUserAgent(array $agent): bool;

	/**
	 * Returns the unverified server host from the headers without checking
	 * whether it is a trusted domain
	 *
	 * @psalm-taint-source input
	 *
	 * @return string Server host
	 * @since 8.1.0
	 */
	public function getInsecureServerHost(): string;

	/**
	 * Returns the server host from the headers, or the first configured
	 * trusted domain if the host isn't in the trusted list
	 *
	 * @return string Server host
	 * @since 8.1.0
	 */
	public function getServerHost(): string;

	/**
	 * If decoding the request content failed, throw an exception.
	 * Currently only \JsonException for json decoding errors,
	 * but in the future may throw other exceptions for other decoding issues.
	 *
	 * @throws \Exception
	 * @since 32.0.0
	 */
	public function throwDecodingExceptionIfAny(): void;

	/**
	 * Returns the format of the response to this request.
	 *
	 * The `Accept` header and the `format` query parameter control the format.
	 *
	 * @return string|null
	 * @since 33.0.0
	 */
	public function getFormat(): ?string;
}
