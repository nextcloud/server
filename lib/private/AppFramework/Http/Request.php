<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\AppFramework\Http;

use OC\Security\CSRF\CsrfToken;
use OC\Security\CSRF\CsrfTokenManager;
use OC\Security\TrustedDomainHelper;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IRequestId;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Default immutable IRequest implementation.
 *
 * @property-read array<string, mixed> $get
 * @property-read array<string, mixed> $post
 * @property-read array<string, mixed>|resource $put
 * @property-read array<string, mixed> $patch
 * @property-read string $method
 * @property-read array<string, mixed> $server
 * @property-read array<string, mixed> $urlParams
 * @property-read array<string, mixed> $cookies
 * @property-read array<string, mixed> $env
 * @property-read array<string, mixed> $files
 * @property-read array<string, mixed> $parameters
 * @template-implements \ArrayAccess<string, mixed>
 */
class Request implements \ArrayAccess, \Countable, IRequest {
	public const USER_AGENT_IE = '/(MSIE)|(Trident)/';
	// Microsoft Edge User Agent from https://msdn.microsoft.com/en-us/library/hh869301(v=vs.85).aspx
	public const USER_AGENT_MS_EDGE = '/^Mozilla\/5\.0 \([^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Chrome\/[0-9.]+ (Mobile Safari|Safari)\/[0-9.]+ Edge?\/[0-9.]+$/';
	// Firefox User Agent from https://developer.mozilla.org/en-US/docs/Web/HTTP/Gecko_user_agent_string_reference
	public const USER_AGENT_FIREFOX = '/^Mozilla\/5\.0 \([^)]+\) Gecko\/[0-9.]+ Firefox\/[0-9.]+$/';
	// Chrome User Agent from https://developer.chrome.com/multidevice/user-agent
	public const USER_AGENT_CHROME = '/^Mozilla\/5\.0 \([^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\)( Ubuntu Chromium\/[0-9.]+|) Chrome\/[0-9.]+ (Mobile Safari|Safari)\/[0-9.]+( (Vivaldi|Brave|OPR)\/[0-9.]+|)$/';
	// Safari User Agent from http://www.useragentstring.com/pages/Safari/
	public const USER_AGENT_SAFARI = '/^Mozilla\/5\.0 \([^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Version\/[0-9.]+ Safari\/[0-9.A-Z]+$/';
	public const USER_AGENT_SAFARI_MOBILE = '/^Mozilla\/5\.0 \((?:Apple-)?iP[^)]+\) AppleWebKit\/[0-9.+]+ \(KHTML, like Gecko\)/';
	// Android Chrome user agent: https://developers.google.com/chrome/mobile/docs/user-agent
	public const USER_AGENT_ANDROID_MOBILE_CHROME = '#Android.*Chrome/[.0-9]*#';
	public const USER_AGENT_FREEBOX = '#^Mozilla/5\.0$#';

	public const REGEX_LOCALHOST = '/^(127\.0\.0\.1|localhost|\[::1\])$/';

	/**
	 * Whether the raw PUT body stream has already been returned.
	 */
	private bool $isPutStreamContentAlreadySent = false;

	/**
	 * Internal request data store.
	 */
	protected array $items = [];

	/**
	 * Magic properties that are exposed directly from $items.
	 *
	 * @var list<string>
	 */
	protected array $allowedKeys = [
		'get',
		'post',
		'files',
		'server',
		'env',
		'cookies',
		'urlParams',
		'parameters',
		'method',
		'requesttoken',
	];

	/**
	 * Whether request-body decoding has already been attempted.
	 */
	protected bool $contentDecoded = false;

	/**
	 * Deferred decoding error from the request body, if any.
	 */
	private ?\JsonException $decodingException = null;

	/**
	 * @param array $vars Associative request data with the following optional keys:
	 *                    - array 'urlParams' route parameters extracted from the URL
	 *                    - array 'get' the $_GET array
	 *                    - array 'post' the $_POST array
	 *                    - array 'files' the $_FILES array
	 *                    - array 'server' the $_SERVER array
	 *                    - array 'env' the $_ENV array
	 *                    - array 'cookies' the $_COOKIE array
	 *                    - string 'method' the HTTP request method, for example GET or POST
	 *                    - string|false 'requesttoken' the request token, or false if unavailable
	 * @see https://www.php.net/manual/en/reserved.variables.php
	 */
	public function __construct(
		array $vars,
		protected IRequestId $requestId,
		protected IConfig $config,
		protected ?CsrfTokenManager $csrfTokenManager = null,
		protected string $inputStream = 'php://input',
	) {
		$this->items['params'] = [];

		if (!array_key_exists('method', $vars)) {
			$vars['method'] = 'GET';
		}

		foreach ($this->allowedKeys as $name) {
			$this->items[$name] = $vars[$name] ?? [];
		}

		$this->items['parameters'] = array_merge(
			$this->items['get'],
			$this->items['post'],
			$this->items['urlParams'],
			$this->items['params']
		);
	}

	/**
	 * Replaces the current URL parameters and merges them into the parameter set.
	 *
	 * URL parameters take precedence over previously merged values with the same
	 * key.
	 *
	 * @param array $parameters
	 *
	 * @internal public only so it can be consumed by OC\AppFramework\App
	 */
	public function setUrlParameters(array $parameters) {
		$this->items['urlParams'] = $parameters;
		$this->items['parameters'] = array_merge(
			$this->items['parameters'],
			$this->items['urlParams']
		);
	}

	/**
	 * Returns the number of merged request parameters.
	 */
	#[\Override]
	public function count(): int {
		return \count($this->items['parameters']);
	}

	/**
	 * Whether a merged request parameter exists.
	 *
	 * ArrayAccess operates on the merged parameter set.
	 *
	 * @param string $offset Parameter name
	 * @return bool
	 */
	#[\Override]
	public function offsetExists($offset): bool {
		return isset($this->items['parameters'][$offset]);
	}

	/**
	 * Returns a merged request parameter value, or null if it is missing.
	 *
	 * @param string $offset Parameter name
	 * @return mixed
	 */
	#[\Override]
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		return $this->items['parameters'][$offset] ?? null;
	}

	/**
	 * Request objects are immutable.
	 *
	 * @param string $offset
	 * @param mixed $value
	 */
	#[\Override]
	public function offsetSet($offset, $value): void {
		throw new \RuntimeException('You cannot change the contents of the request object');
	}

	/**
	 * Request objects are immutable.
	 *
	 * @param string $offset
	 */
	#[\Override]
	public function offsetUnset($offset): void {
		throw new \RuntimeException('You cannot change the contents of the request object');
	}

	/**
	 * Request objects are immutable.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {
		throw new \RuntimeException('You cannot change the contents of the request object');
	}

	/**
	 * Returns request data through magic property access.
	 *
	 * Named properties read from the merged parameter set. Method-specific
	 * properties (`get`, `post`, `put`, `patch`) are only available for the
	 * matching HTTP method and throw a \LogicException otherwise.
	 *
	 * Depending on the method and content type, `put` may return either parsed
	 * parameters or a readable stream for the raw request body.
	 *
	 * @param string $name Property name
	 * @throws \LogicException
	 * @return mixed|null
	 */
	public function __get($name) {
		switch ($name) {
			case 'put':
			case 'patch':
			case 'get':
			case 'post':
				if ($this->method !== strtoupper($name)) {
					throw new \LogicException(sprintf('%s cannot be accessed in a %s request.', $name, $this->method));
				}
				return $this->getContent();
			case 'files':
			case 'server':
			case 'env':
			case 'cookies':
			case 'urlParams':
			case 'method':
				return $this->items[$name] ?? null;
			case 'parameters':
			case 'params':
				if ($this->isPutStreamContent()) {
					return $this->items['parameters'];
				}
				return $this->getContent();
			default:
				return isset($this[$name])
					? $this[$name]
					: null;
		}
	}

	/**
	 * Whether a magic property is available.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name) {
		if (\in_array($name, $this->allowedKeys, true)) {
			return true;
		}
		return isset($this->items['parameters'][$name]);
	}

	/**
	 * Request objects are immutable.
	 *
	 * @param string $id
	 */
	public function __unset($id) {
		throw new \RuntimeException('You cannot change the contents of the request object');
	}

	#[\Override]
	public function getHeader(string $name): string {
		$elementName = strtoupper(str_replace('-', '_', $name));

		// Check if standard HTTP header
		$clientHeaderKey = 'HTTP_' . $elementName;
		if (isset($this->server[$clientHeaderKey])) {
			return $this->server[$clientHeaderKey];
		}

		// Check if special request-related element
		$specialKeys = [
			'CONTENT_TYPE' => true,
			'CONTENT_LENGTH' => true,
			'REMOTE_ADDR' => true,
		];

		if (isset($specialKeys[$elementName]) && isset($this->server[$elementName])) {
			return $this->server[$elementName];
		}

		return '';
	}

	#[\Override]
	public function getParam(string $key, $default = null) {
		return isset($this->parameters[$key])
			? $this->parameters[$key]
			: $default;
	}

	#[\Override]
	public function getParams(): array {
		return is_array($this->parameters) ? $this->parameters : [];
	}

	#[\Override]
	public function getMethod(): string {
		return $this->method;
	}

	#[\Override]
	public function getUploadedFile(string $key) {
		return isset($this->files[$key]) ? $this->files[$key] : null;
	}

	#[\Override]
	public function getEnv(string $key) {
		return isset($this->env[$key]) ? $this->env[$key] : null;
	}

	#[\Override]
	public function getCookie(string $key) {
		return isset($this->cookies[$key]) ? $this->cookies[$key] : null;
	}

	/**
	 * Returns request body content for method-specific magic accessors.
	 *
	 * For PUT requests with a non-empty body that is neither JSON nor
	 * form-encoded, a readable stream resource for the raw body is returned.
	 * Otherwise, parsed parameters are returned as an array.
	 *
	 * @return array|string|resource The request body content or a resource for the raw body stream
	 * @throws \LogicException
	 */
	protected function getContent() {
		// If the content cannot be parsed into parameters, return a raw body stream.
		if ($this->isPutStreamContent()) {
			if ($this->isPutStreamContentAlreadySent) {
				throw new \LogicException(
					'"put" can only be accessed once if not '
					. 'application/x-www-form-urlencoded or application/json.'
				);
			}
			$this->isPutStreamContentAlreadySent = true;
			return fopen($this->inputStream, 'rb');
		} else {
			$this->decodeContent();
			return $this->items['parameters'];
		}
	}

	private function isPutStreamContent(): bool {
		return $this->method === 'PUT'
			&& $this->getHeader('Content-Length') !== '0'
			&& $this->getHeader('Content-Length') !== ''
			&& !str_contains($this->getHeader('Content-Type'), 'application/x-www-form-urlencoded')
			&& !str_contains($this->getHeader('Content-Type'), 'application/json');
	}

	/**
	 * Decodes the request body, if applicable, and merges decoded parameters
	 * into the parameter set.
	 *
	 * JSON-compatible content types are decoded from the input stream. For
	 * non-GET and non-POST form-encoded requests, the input stream is parsed
	 * into parameters. Decoding errors are stored and can later be rethrown via
	 * throwDecodingExceptionIfAny().
	 */
	protected function decodeContent() {
		if ($this->contentDecoded) {
			return;
		}
		$params = [];

		// JSON-compatible content types must be decoded manually.
		if (preg_match(self::JSON_CONTENT_TYPE_REGEX, $this->getHeader('Content-Type')) === 1) {
			$content = file_get_contents($this->inputStream);
			if ($content !== '') {
				try {
					$params = json_decode($content, true, flags:JSON_THROW_ON_ERROR);
				} catch (\JsonException $e) {
					$this->decodingException = $e;
				}
			}
			if (\is_array($params) && \count($params) > 0) {
				$this->items['params'] = $params;
				if ($this->method === 'POST') {
					$this->items['post'] = $params;
				}
			}
			// Handle form-encoded request bodies for methods other than GET and POST.
		} elseif ($this->method !== 'GET'
				&& $this->method !== 'POST'
				&& str_contains($this->getHeader('Content-Type'), 'application/x-www-form-urlencoded')) {
			parse_str(file_get_contents($this->inputStream), $params);
			if (\is_array($params)) {
				$this->items['params'] = $params;
			}
		}

		if (\is_array($params)) {
			$this->items['parameters'] = array_merge($this->items['parameters'], $params);
		}
		$this->contentDecoded = true;
	}

	#[\Override]
	public function throwDecodingExceptionIfAny(): void {
		if ($this->decodingException !== null) {
			throw $this->decodingException;
		}
	}

	#[\Override]
	public function passesCSRFCheck(): bool {
		if ($this->csrfTokenManager === null) {
			return false;
		}

		if (!$this->passesStrictCookieCheck()) {
			return false;
		}

		if ($this->getHeader('OCS-APIRequest') !== '') {
			return true;
		}

		if (isset($this->items['get']['requesttoken'])) {
			$token = $this->items['get']['requesttoken'];
		} elseif (isset($this->items['post']['requesttoken'])) {
			$token = $this->items['post']['requesttoken'];
		} elseif (isset($this->items['server']['HTTP_REQUESTTOKEN'])) {
			$token = $this->items['server']['HTTP_REQUESTTOKEN'];
		} else {
			//no token found.
			return false;
		}
		$token = new CsrfToken($token);

		return $this->csrfTokenManager->isTokenValid($token);
	}

	/**
	 * Whether cookie-based same-site checks are required for this request.
	 */
	private function cookieCheckRequired(): bool {
		if ($this->getHeader('OCS-APIREQUEST')) {
			return false;
		}
		if ($this->getCookie(session_name()) === null && $this->getCookie('nc_token') === null) {
			return false;
		}

		return true;
	}

	/**
	 * Wrapper around session_get_cookie_params().
	 */
	public function getCookieParams(): array {
		return session_get_cookie_params();
	}

	/**
	 * Returns the cookie name with the __Host- prefix applied when appropriate.
	 */
	protected function getProtectedCookieName(string $name): string {
		$cookieParams = $this->getCookieParams();
		$prefix = '';
		if ($cookieParams['secure'] === true && $cookieParams['path'] === '/') {
			$prefix = '__Host-';
		}

		return $prefix . $name;
	}

	#[\Override]
	public function passesStrictCookieCheck(): bool {
		if (!$this->cookieCheckRequired()) {
			return true;
		}

		$cookieName = $this->getProtectedCookieName('nc_sameSiteCookiestrict');
		if ($this->getCookie($cookieName) === 'true'
			&& $this->passesLaxCookieCheck()) {
			return true;
		}
		return false;
	}

	#[\Override]
	public function passesLaxCookieCheck(): bool {
		if (!$this->cookieCheckRequired()) {
			return true;
		}

		$cookieName = $this->getProtectedCookieName('nc_sameSiteCookielax');
		if ($this->getCookie($cookieName) === 'true') {
			return true;
		}
		return false;
	}

	#[\Override]
	public function getId(): string {
		return $this->requestId->getId();
	}

	/**
	 * Checks whether the given remote address matches one of the configured
	 * trusted proxies.
	 *
	 * Invalid trusted proxy configuration is treated as non-matching.
	 *
	 * @return bool true if $remoteAddress matches a trusted proxy, false otherwise
	 */
	protected function isTrustedProxy($trustedProxies, $remoteAddress) {
		try {
			return IpUtils::checkIp($remoteAddress, $trustedProxies);
		} catch (\Throwable) {
			// Cannot log through the regular logger here because it may depend on
			// getRemoteAddress(), which would create a cyclic dependency.
			error_log('Nextcloud trustedProxies has malformed entries');
			return false;
		}
	}

	#[\Override]
	public function getRemoteAddress(): string {
		$remoteAddress = isset($this->server['REMOTE_ADDR']) ? $this->server['REMOTE_ADDR'] : '';
		$trustedProxies = $this->config->getSystemValue('trusted_proxies', []);

		if (\is_array($trustedProxies) && $this->isTrustedProxy($trustedProxies, $remoteAddress)) {
			$forwardedForHeaders = $this->config->getSystemValue('forwarded_for_headers', [
				'HTTP_X_FORWARDED_FOR'
				// only have one default, so we cannot ship an insecure product out of the box
			]);

			// Read the x-forwarded-for headers and values in reverse order as per
			// https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Forwarded-For#selecting_an_ip_address
			foreach (array_reverse($forwardedForHeaders) as $header) {
				if (isset($this->server[$header])) {
					foreach (array_reverse(explode(',', $this->server[$header])) as $IP) {
						$IP = trim($IP);
						$colons = substr_count($IP, ':');
						if ($colons > 1) {
							// Extract IP from string with brackets and optional port
							if (preg_match('/^\[(.+?)\](?::\d+)?$/', $IP, $matches) && isset($matches[1])) {
								$IP = $matches[1];
							}
						} elseif ($colons === 1) {
							// IPv4 with port
							$IP = substr($IP, 0, strpos($IP, ':'));
						}

						if ($this->isTrustedProxy($trustedProxies, $IP)) {
							continue;
						}

						if (filter_var($IP, FILTER_VALIDATE_IP) !== false) {
							return $IP;
						}
					}
				}
			}
		}

		return $remoteAddress;
	}

	private function isOverwriteCondition(): bool {
		$regex = '/' . $this->config->getSystemValueString('overwritecondaddr', '') . '/';
		$remoteAddr = isset($this->server['REMOTE_ADDR']) ? $this->server['REMOTE_ADDR'] : '';
		return $regex === '//' || preg_match($regex, $remoteAddr) === 1;
	}

	#[\Override]
	public function getServerProtocol(): string {
		$proto = 'http';

		if ($this->config->getSystemValueString('overwriteprotocol') !== ''
			&& $this->isOverwriteCondition()
		) {
			$proto = strtolower($this->config->getSystemValueString('overwriteprotocol'));
		} elseif ($this->fromTrustedProxy()
			&& isset($this->server['HTTP_X_FORWARDED_PROTO'])
		) {
			if (str_contains($this->server['HTTP_X_FORWARDED_PROTO'], ',')) {
				$parts = explode(',', $this->server['HTTP_X_FORWARDED_PROTO']);
				$proto = strtolower(trim($parts[0]));
			} else {
				$proto = strtolower($this->server['HTTP_X_FORWARDED_PROTO']);
			}
		} elseif (!empty($this->server['HTTPS'])
			&& $this->server['HTTPS'] !== 'off'
		) {
			$proto = 'https';
		}

		if ($proto !== 'https' && $proto !== 'http') {
			// log unrecognized value so admin has a chance to fix it
			Server::get(LoggerInterface::class)->critical(
				'Server protocol is malformed [falling back to http] (check overwriteprotocol and/or X-Forwarded-Proto to remedy): ' . $proto,
				['app' => 'core']
			);
		}

		// default to http if provided an invalid value
		return $proto === 'https' ? 'https' : 'http';
	}

	#[\Override]
	public function getHttpProtocol(): string {
		$claimedProtocol = $this->server['SERVER_PROTOCOL'] ?? '';

		if (\is_string($claimedProtocol)) {
			$claimedProtocol = strtoupper($claimedProtocol);
		}

		$validProtocols = [
			'HTTP/1.0',
			'HTTP/1.1',
			'HTTP/2',
		];

		if (\in_array($claimedProtocol, $validProtocols, true)) {
			return $claimedProtocol;
		}

		return 'HTTP/1.1';
	}

	#[\Override]
	public function getRequestUri(): string {
		$uri = isset($this->server['REQUEST_URI']) ? $this->server['REQUEST_URI'] : '';
		if ($this->config->getSystemValueString('overwritewebroot') !== '' && $this->isOverwriteCondition()) {
			$uri = $this->getScriptName() . substr($uri, \strlen($this->server['SCRIPT_NAME']));
		}
		return $uri;
	}

	#[\Override]
	public function getRawPathInfo(): string {
		$requestUri = isset($this->server['REQUEST_URI']) ? $this->server['REQUEST_URI'] : '';
		// remove too many slashes - can be caused by reverse proxy configuration
		$requestUri = preg_replace('%/{2,}%', '/', $requestUri);

		// Remove the query string from REQUEST_URI
		if ($pos = strpos($requestUri, '?')) {
			$requestUri = substr($requestUri, 0, $pos);
		}

		$scriptName = $this->server['SCRIPT_NAME'] ?? '';
		$pathInfo = $requestUri;

		// strip off the script name's dir and file name
		// FIXME: Sabre does not really belong here
		[$path, $name] = \Sabre\Uri\split($scriptName);
		if (!empty($path)) {
			if ($path === $pathInfo || str_starts_with($pathInfo, $path . '/')) {
				$pathInfo = substr($pathInfo, \strlen($path));
			} else {
				throw new \Exception("The requested uri($requestUri) cannot be processed by the script '$scriptName')");
			}
		}
		if ($name === null) {
			$name = '';
		}

		if (str_starts_with($pathInfo, '/' . $name)) {
			$pathInfo = substr($pathInfo, \strlen($name) + 1);
		}
		if ($name !== '' && str_starts_with($pathInfo, $name)) {
			$pathInfo = substr($pathInfo, \strlen($name));
		}
		if ($pathInfo === false || $pathInfo === '/') {
			return '';
		} else {
			return $pathInfo;
		}
	}

	#[\Override]
	public function getPathInfo(): string|false {
		$pathInfo = $this->getRawPathInfo();
		return \Sabre\HTTP\decodePath($pathInfo);
	}

	#[\Override]
	public function getScriptName(): string {
		$name = $this->server['SCRIPT_NAME'] ?? '';
		$overwriteWebRoot = $this->config->getSystemValueString('overwritewebroot');
		if ($overwriteWebRoot !== '' && $this->isOverwriteCondition()) {
			// FIXME: This code is untestable due to __DIR__, also that hardcoded path is really dangerous
			$serverRoot = str_replace('\\', '/', substr(__DIR__, 0, -\strlen('lib/private/appframework/http/')));
			$suburi = str_replace('\\', '/', substr(realpath($this->server['SCRIPT_FILENAME']), \strlen($serverRoot)));
			$name = '/' . ltrim($overwriteWebRoot . $suburi, '/');
		}
		return $name;
	}

	#[\Override]
	public function isUserAgent(array $agent): bool {
		if (!isset($this->server['HTTP_USER_AGENT'])) {
			return false;
		}
		foreach ($agent as $regex) {
			if (preg_match($regex, $this->server['HTTP_USER_AGENT'])) {
				return true;
			}
		}
		return false;
	}

	#[\Override]
	public function getInsecureServerHost(): string {
		if ($this->fromTrustedProxy() && $this->getOverwriteHost() !== null) {
			return $this->getOverwriteHost();
		}

		$host = 'localhost';
		if ($this->fromTrustedProxy() && isset($this->server['HTTP_X_FORWARDED_HOST'])) {
			if (str_contains($this->server['HTTP_X_FORWARDED_HOST'], ',')) {
				$parts = explode(',', $this->server['HTTP_X_FORWARDED_HOST']);
				$host = trim(current($parts));
			} else {
				$host = $this->server['HTTP_X_FORWARDED_HOST'];
			}
		} else {
			if (isset($this->server['HTTP_HOST'])) {
				$host = $this->server['HTTP_HOST'];
			} elseif (isset($this->server['SERVER_NAME'])) {
				$host = $this->server['SERVER_NAME'];
			}
		}

		return $host;
	}

	#[\Override]
	public function getServerHost(): string {
		// overwritehost is always trusted
		$host = $this->getOverwriteHost();
		if ($host !== null) {
			return $host;
		}

		// get the host from the headers
		$host = $this->getInsecureServerHost();

		// Verify that the host is a trusted domain if the trusted domains
		// are defined
		// If no trusted domain is provided the first trusted domain is returned
		$trustedDomainHelper = new TrustedDomainHelper($this->config);
		if ($trustedDomainHelper->isTrustedDomain($host)) {
			return $host;
		}

		$trustedList = (array)$this->config->getSystemValue('trusted_domains', []);
		if (count($trustedList) > 0) {
			return reset($trustedList);
		}

		return '';
	}

	/**
	 * Returns the overwritehost config value if configured and applicable.
	 *
	 * @return string|null
	 */
	private function getOverwriteHost() {
		if ($this->config->getSystemValueString('overwritehost') !== '' && $this->isOverwriteCondition()) {
			return $this->config->getSystemValueString('overwritehost');
		}
		return null;
	}

	private function fromTrustedProxy(): bool {
		$remoteAddress = isset($this->server['REMOTE_ADDR']) ? $this->server['REMOTE_ADDR'] : '';
		$trustedProxies = $this->config->getSystemValue('trusted_proxies', []);

		return \is_array($trustedProxies) && $this->isTrustedProxy($trustedProxies, $remoteAddress);
	}

	#[\Override]
	public function getFormat(): ?string {
		$format = $this->getParam('format');
		if ($format !== null) {
			return $format;
		}

		$prefix = 'application/';
		$headers = explode(',', $this->getHeader('Accept'));
		foreach ($headers as $header) {
			$header = strtolower(trim($header));

			if (str_starts_with($header, $prefix)) {
				return substr($header, strlen($prefix));
			}
		}

		return null;
	}
}
