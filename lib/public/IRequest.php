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
	 * Returns the request method.
	 *
	 * @return string the HTTP method, for example GET, POST, PUT, or PATCH
	 * @since 6.0.0
	 */
	public function getMethod(): string;

	/**
	 * Returns an uploaded file entry from the `$_FILES` data, if present.
	 *
	 * @param string $key the file field name
	 * @return array|null the matching uploaded file entry, or null if missing
	 * @since 6.0.0
	 */
	public function getUploadedFile(string $key);

	/**
	 * Returns an environment value from the request environment, if present.
	 *
	 * @param string $key the environment variable name
	 * @return mixed|null the environment value, or null if missing
	 * @since 6.0.0
	 */
	public function getEnv(string $key);

	/**
	 * Returns a cookie value, if present.
	 *
	 * @psalm-taint-source input
	 *
	 * @param string $key the cookie name
	 * @return string|null the cookie value, or null if missing
	 * @since 6.0.0
	 */
	public function getCookie(string $key);

	/**
	 * Checks whether the request passes CSRF validation.
	 *
	 * Depending on the request, this may include same-site cookie checks and
	 * token validation from request parameters or headers. OCS API requests are
	 * handled specially by the implementation (if the OCS-APIRequest header is
	 * included in the request).
	 *
	 * @return bool true if the request passes CSRF validation
	 * @since 6.0.0
	 */
	public function passesCSRFCheck(): bool;

	/**
	 * Checks whether the strict same-site cookie requirement is satisfied when
	 * session or authentication cookies are part of the request.
	 *
	 * @return bool true if the strict cookie check passes
	 * @since 9.0.0
	 */
	public function passesStrictCookieCheck(): bool;

	/**
	 * Checks whether the lax same-site cookie requirement is satisfied when
	 * session or authentication cookies are part of the request.
	 *
	 * @return bool true if the lax cookie check passes
	 * @since 9.0.0
	 */
	public function passesLaxCookieCheck(): bool;

	/**
	 * Returns a request identifier intended primarily for logging and tracing.
	 *
	 * The value is not guaranteed to be globally unique. If `mod_unique_id` is
	 * installed, that value may be used by the implementation.
	 *
	 * @return string
	 * @since 8.1.0
	 */
	public function getId(): string;

	/**
	 * Returns the effective remote IP address.
	 *
	 * If the connection comes from a trusted proxy and `forwarded_for_headers`
	 * is configured, the client IP from those forwarded headers is used
	 * instead.
	 *
	 * Do not use `$_SERVER['REMOTE_ADDR']` directly when this method is
	 * available.
	 *
	 * @return string IP address
	 * @since 8.1.0
	 */
	public function getRemoteAddress(): string;

	/**
	 * Returns the effective server protocol.
	 *
	 * Respects reverse proxies and load balancers. Precedence:
	 *   1. `overwriteprotocol` config value
	 *   2. `X-Forwarded-Proto` header value
	 *   3. `$_SERVER['HTTPS']` value
	 *
	 *  Invalid values fall back to `http`.
	 *
	 * @return string Server protocol: `http` or `https`
	 * @since 8.1.0
	 */
	public function getServerProtocol(): string;

	/**
	 * Returns the HTTP protocol version used for the request.
	 *
	 * @return string HTTP protocol, for example HTTP/2, HTTP/1.1, or HTTP/1.0
	 * @since 8.2.0
	 */
	public function getHttpProtocol(): string;

	/**
	 * Returns the request URI, taking reverse-proxy and overwrite settings into
	 * account.
	 *
	 * @psalm-taint-source input
	 *
	 * @return string
	 * @since 8.1.0
	 */
	public function getRequestUri(): string;

	/**
	 * Returns raw path info from the request without URL decoding.
	 *
	 * @psalm-taint-source input
	 *
	 * @throws \Exception
	 * @return string path info
	 * @since 8.1.0
	 */
	public function getRawPathInfo(): string;

	/**
	 * Returns decoded path info from the request.
	 *
	 * @psalm-taint-source input
	 *
	 * @throws \Exception
	 * @return string|false path info, or false when it cannot be determined
	 * @since 8.1.0
	 */
	public function getPathInfo();

	/**
	 * Returns the effective script name, taking reverse-proxy and overwrite
	 * settings into account.
	 *
	 * @return string the script name
	 * @since 8.1.0
	 */
	public function getScriptName(): string;

	/**
	 * Checks whether the current user agent matches at least one of the given
	 * regular expressions.
	 *
	 * @param array $agent array of user-agent regex patterns
	 * @return bool true if at least one pattern matches, false otherwise
	 * @since 8.1.0
	 */
	public function isUserAgent(array $agent): bool;

	/**
	 * Returns the effective host value without validating it against the trusted
	 * domains configuration.
	 *
	 * This may be derived from request headers, proxy headers, or server
	 * variables, depending on the deployment setup.
	 *
	 * @psalm-taint-source input
	 *
	 * @return string server host
	 * @since 8.1.0
	 */
	public function getInsecureServerHost(): string;

	/**
	 * Returns the validated effective server host.
	 *
	 * The implementation may use overwrite host configuration first. Otherwise
	 * it derives the host from the request and returns it only if it is trusted;
	 * if not, it falls back to the first configured trusted domain.
	 *
	 * @return string server host
	 * @since 8.1.0
	 */
	public function getServerHost(): string;

	/**
	 * Throws any stored request-content decoding exception.
	 *
	 * Currently this is used for JSON decoding errors, but implementations may
	 * throw other decoding-related exceptions in the future.
	 *
	 * @throws \Exception
	 * @since 32.0.0
	 */
	public function throwDecodingExceptionIfAny(): void;

	/**
	 * Returns the requested response format, if it can be determined.
	 *
	 * The `format` request parameter takes precedence. Otherwise the format may
	 * be inferred from the `Accept` header.
	 *
	 * @return string|null
	 * @since 33.0.0
	 */
	public function getFormat(): ?string;
}
