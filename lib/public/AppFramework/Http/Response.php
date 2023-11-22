<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Clement Wong <git@clement.hk>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Base class for responses. Also used to just send headers.
 *
 * It handles headers, HTTP status code, last modified and ETag.
 * @since 6.0.0
 * @template S of int
 * @template H of array<string, mixed>
 */
class Response {
	/**
	 * Headers
	 * @var H
	 */
	private $headers;


	/**
	 * Cookies that will be need to be constructed as header
	 * @var array
	 */
	private $cookies = [];


	/**
	 * HTTP status code - defaults to STATUS OK
	 * @var S
	 */
	private $status;


	/**
	 * Last modified date
	 * @var \DateTime
	 */
	private $lastModified;


	/**
	 * ETag
	 * @var string
	 */
	private $ETag;

	/** @var ContentSecurityPolicy|null Used Content-Security-Policy */
	private $contentSecurityPolicy = null;

	/** @var FeaturePolicy */
	private $featurePolicy;

	/** @var bool */
	private $throttled = false;
	/** @var array */
	private $throttleMetadata = [];

	/**
	 * @param S $status
	 * @param H $headers
	 * @since 17.0.0
	 */
	public function __construct(int $status = Http::STATUS_OK, array $headers = []) {
		$this->setStatus($status);
		$this->setHeaders($headers);
	}

	/**
	 * Caches the response
	 *
	 * @param int $cacheSeconds amount of seconds the response is fresh, 0 to disable cache.
	 * @param bool $public whether the page should be cached by public proxy. Usually should be false, unless this is a static resources.
	 * @param bool $immutable whether browser should treat the resource as immutable and not ask the server for each page load if the resource changed.
	 * @return $this
	 * @since 6.0.0 - return value was added in 7.0.0
	 */
	public function cacheFor(int $cacheSeconds, bool $public = false, bool $immutable = false) {
		if ($cacheSeconds > 0) {
			$cacheStore = $public ? 'public' : 'private';
			$this->addHeader('Cache-Control', sprintf('%s, max-age=%s, %s', $cacheStore, $cacheSeconds, ($immutable ? 'immutable' : 'must-revalidate')));

			// Set expires header
			$expires = new \DateTime();
			/** @var ITimeFactory $time */
			$time = \OCP\Server::get(ITimeFactory::class);
			$expires->setTimestamp($time->getTime());
			$expires->add(new \DateInterval('PT'.$cacheSeconds.'S'));
			$this->addHeader('Expires', $expires->format(\DateTimeInterface::RFC2822));
		} else {
			$this->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
			unset($this->headers['Expires']);
		}

		return $this;
	}

	/**
	 * Adds a new cookie to the response
	 * @param string $name The name of the cookie
	 * @param string $value The value of the cookie
	 * @param \DateTime|null $expireDate Date on that the cookie should expire, if set
	 * 									to null cookie will be considered as session
	 * 									cookie.
	 * @param string $sameSite The samesite value of the cookie. Defaults to Lax. Other possibilities are Strict or None
	 * @return $this
	 * @since 8.0.0
	 */
	public function addCookie($name, $value, \DateTime $expireDate = null, $sameSite = 'Lax') {
		$this->cookies[$name] = ['value' => $value, 'expireDate' => $expireDate, 'sameSite' => $sameSite];
		return $this;
	}


	/**
	 * Set the specified cookies
	 * @param array $cookies array('foo' => array('value' => 'bar', 'expire' => null))
	 * @return $this
	 * @since 8.0.0
	 */
	public function setCookies(array $cookies) {
		$this->cookies = $cookies;
		return $this;
	}


	/**
	 * Invalidates the specified cookie
	 * @param string $name
	 * @return $this
	 * @since 8.0.0
	 */
	public function invalidateCookie($name) {
		$this->addCookie($name, 'expired', new \DateTime('1971-01-01 00:00'));
		return $this;
	}

	/**
	 * Invalidates the specified cookies
	 * @param array $cookieNames array('foo', 'bar')
	 * @return $this
	 * @since 8.0.0
	 */
	public function invalidateCookies(array $cookieNames) {
		foreach ($cookieNames as $cookieName) {
			$this->invalidateCookie($cookieName);
		}
		return $this;
	}

	/**
	 * Returns the cookies
	 * @return array
	 * @since 8.0.0
	 */
	public function getCookies() {
		return $this->cookies;
	}

	/**
	 * Adds a new header to the response that will be called before the render
	 * function
	 * @param string $name The name of the HTTP header
	 * @param string $value The value, null will delete it
	 * @return $this
	 * @since 6.0.0 - return value was added in 7.0.0
	 */
	public function addHeader($name, $value) {
		$name = trim($name);  // always remove leading and trailing whitespace
		// to be able to reliably check for security
		// headers

		if ($this->status === Http::STATUS_NOT_MODIFIED
			&& stripos($name, 'x-') === 0) {
			/** @var IConfig $config */
			$config = \OC::$server->get(IConfig::class);

			if ($config->getSystemValueBool('debug', false)) {
				\OC::$server->get(LoggerInterface::class)->error('Setting custom header on a 204 or 304 is not supported (Header: {header})', [
					'header' => $name,
				]);
			}
		}

		if (is_null($value)) {
			unset($this->headers[$name]);
		} else {
			$this->headers[$name] = $value;
		}

		return $this;
	}


	/**
	 * Set the headers
	 * @template NewH as array<string, mixed>
	 * @param NewH $headers value header pairs
	 * @psalm-this-out static<S, NewH>
	 * @return static
	 * @since 8.0.0
	 */
	public function setHeaders(array $headers): static {
		/** @psalm-suppress InvalidPropertyAssignmentValue Expected due to @psalm-this-out */
		$this->headers = $headers;

		return $this;
	}


	/**
	 * Returns the set headers
	 * @return array{X-Request-Id: string, Cache-Control: string, Content-Security-Policy: string, Feature-Policy: string, X-Robots-Tag: string, Last-Modified?: string, ETag?: string, ...H} the headers
	 * @since 6.0.0
	 */
	public function getHeaders() {
		/** @var IRequest $request */
		/**
		 * @psalm-suppress UndefinedClass
		 */
		$request = \OC::$server->get(IRequest::class);
		$mergeWith = [
			'X-Request-Id' => $request->getId(),
			'Cache-Control' => 'no-cache, no-store, must-revalidate',
			'Content-Security-Policy' => $this->getContentSecurityPolicy()->buildPolicy(),
			'Feature-Policy' => $this->getFeaturePolicy()->buildPolicy(),
			'X-Robots-Tag' => 'noindex, nofollow',
		];

		if ($this->lastModified) {
			$mergeWith['Last-Modified'] = $this->lastModified->format(\DateTimeInterface::RFC2822);
		}

		if ($this->ETag) {
			$mergeWith['ETag'] = '"' . $this->ETag . '"';
		}

		return array_merge($mergeWith, $this->headers);
	}


	/**
	 * By default renders no output
	 * @return string
	 * @since 6.0.0
	 */
	public function render() {
		return '';
	}


	/**
	 * Set response status
	 * @template NewS as int
	 * @param NewS $status a HTTP status code, see also the STATUS constants
	 * @psalm-this-out static<NewS, H>
	 * @return static
	 * @since 6.0.0 - return value was added in 7.0.0
	 */
	public function setStatus($status): static {
		/** @psalm-suppress InvalidPropertyAssignmentValue Expected due to @psalm-this-out */
		$this->status = $status;

		return $this;
	}

	/**
	 * Set a Content-Security-Policy
	 * @param EmptyContentSecurityPolicy $csp Policy to set for the response object
	 * @return $this
	 * @since 8.1.0
	 */
	public function setContentSecurityPolicy(EmptyContentSecurityPolicy $csp) {
		$this->contentSecurityPolicy = $csp;
		return $this;
	}

	/**
	 * Get the currently used Content-Security-Policy
	 * @return EmptyContentSecurityPolicy|null Used Content-Security-Policy or null if
	 *                                    none specified.
	 * @since 8.1.0
	 */
	public function getContentSecurityPolicy() {
		if ($this->contentSecurityPolicy === null) {
			$this->setContentSecurityPolicy(new EmptyContentSecurityPolicy());
		}
		return $this->contentSecurityPolicy;
	}


	/**
	 * @since 17.0.0
	 */
	public function getFeaturePolicy(): EmptyFeaturePolicy {
		if ($this->featurePolicy === null) {
			$this->setFeaturePolicy(new EmptyFeaturePolicy());
		}
		return $this->featurePolicy;
	}

	/**
	 * @since 17.0.0
	 */
	public function setFeaturePolicy(EmptyFeaturePolicy $featurePolicy): self {
		$this->featurePolicy = $featurePolicy;

		return $this;
	}



	/**
	 * Get response status
	 * @since 6.0.0
	 * @return S
	 */
	public function getStatus() {
		return $this->status;
	}


	/**
	 * Get the ETag
	 * @return string the etag
	 * @since 6.0.0
	 */
	public function getETag() {
		return $this->ETag;
	}


	/**
	 * Get "last modified" date
	 * @return \DateTime RFC2822 formatted last modified date
	 * @since 6.0.0
	 */
	public function getLastModified() {
		return $this->lastModified;
	}


	/**
	 * Set the ETag
	 * @param string $ETag
	 * @return Response Reference to this object
	 * @since 6.0.0 - return value was added in 7.0.0
	 */
	public function setETag($ETag) {
		$this->ETag = $ETag;

		return $this;
	}


	/**
	 * Set "last modified" date
	 * @param \DateTime $lastModified
	 * @return Response Reference to this object
	 * @since 6.0.0 - return value was added in 7.0.0
	 */
	public function setLastModified($lastModified) {
		$this->lastModified = $lastModified;

		return $this;
	}

	/**
	 * Marks the response as to throttle. Will be throttled when the
	 * @BruteForceProtection annotation is added.
	 *
	 * @param array $metadata
	 * @since 12.0.0
	 */
	public function throttle(array $metadata = []) {
		$this->throttled = true;
		$this->throttleMetadata = $metadata;
	}

	/**
	 * Returns the throttle metadata, defaults to empty array
	 *
	 * @return array
	 * @since 13.0.0
	 */
	public function getThrottleMetadata() {
		return $this->throttleMetadata;
	}

	/**
	 * Whether the current response is throttled.
	 *
	 * @since 12.0.0
	 */
	public function isThrottled() {
		return $this->throttled;
	}
}
