<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
class OC_Response {
	/**
	 * Sets the content disposition header (with possible workarounds)
	 * @param string $filename file name
	 * @param string $type disposition type, either 'attachment' or 'inline'
	 */
	public static function setContentDispositionHeader($filename, $type = 'attachment') {
		if (\OC::$server->getRequest()->isUserAgent(
			[
				\OC\AppFramework\Http\Request::USER_AGENT_IE,
				\OC\AppFramework\Http\Request::USER_AGENT_ANDROID_MOBILE_CHROME,
				\OC\AppFramework\Http\Request::USER_AGENT_FREEBOX,
			])) {
			header('Content-Disposition: ' . rawurlencode($type) . '; filename="' . rawurlencode($filename) . '"');
		} else {
			header('Content-Disposition: ' . rawurlencode($type) . '; filename*=UTF-8\'\'' . rawurlencode($filename)
												 . '; filename="' . rawurlencode($filename) . '"');
		}
	}

	/**
	 * Sets the content length header (with possible workarounds)
	 * @param string|int|float $length Length to be sent
	 */
	public static function setContentLengthHeader($length) {
		if (PHP_INT_SIZE === 4) {
			if ($length > PHP_INT_MAX && stripos(PHP_SAPI, 'apache') === 0) {
				// Apache PHP SAPI casts Content-Length headers to PHP integers.
				// This enforces a limit of PHP_INT_MAX (2147483647 on 32-bit
				// platforms). So, if the length is greater than PHP_INT_MAX,
				// we just do not send a Content-Length header to prevent
				// bodies from being received incompletely.
				return;
			}
			// Convert signed integer or float to unsigned base-10 string.
			$lfh = new \OC\LargeFileHelper;
			$length = $lfh->formatUnsignedInteger($length);
		}
		header('Content-Length: '.$length);
	}

	/**
	 * This function adds some security related headers to all requests served via base.php
	 * The implementation of this function has to happen here to ensure that all third-party
	 * components (e.g. SabreDAV) also benefit from this headers.
	 */
	public static function addSecurityHeaders() {
		/**
		 * FIXME: Content Security Policy for legacy ownCloud components. This
		 * can be removed once \OCP\AppFramework\Http\Response from the AppFramework
		 * is used everywhere.
		 * @see \OCP\AppFramework\Http\Response::getHeaders
		 */
		$policy = 'default-src \'self\'; '
			. 'script-src \'self\' \'nonce-'.\OC::$server->getContentSecurityPolicyNonceManager()->getNonce().'\'; '
			. 'style-src \'self\' \'unsafe-inline\'; '
			. 'frame-src *; '
			. 'img-src * data: blob:; '
			. 'font-src \'self\' data:; '
			. 'media-src *; '
			. 'connect-src *; '
			. 'object-src \'none\'; '
			. 'base-uri \'self\'; ';
		header('Content-Security-Policy:' . $policy);

		// Send fallback headers for installations that don't have the possibility to send
		// custom headers on the webserver side
		if (getenv('modHeadersAvailable') !== 'true') {
			header('Referrer-Policy: no-referrer'); // https://www.w3.org/TR/referrer-policy/
			header('X-Content-Type-Options: nosniff'); // Disable sniffing the content type for IE
			header('X-Frame-Options: SAMEORIGIN'); // Disallow iFraming from other domains
			header('X-Permitted-Cross-Domain-Policies: none'); // https://www.adobe.com/devnet/adobe-media-server/articles/cross-domain-xml-for-streaming.html
			header('X-Robots-Tag: noindex, nofollow'); // https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag
			header('X-XSS-Protection: 1; mode=block'); // Enforce browser based XSS filters
		}
	}

	/**
	 * This function adds the CORS headers if the requester domain is white-listed
	 *
	 * @param \OCP\AppFramework\Http\Response|Sabre\HTTP\ResponseInterface $response
	 * @param string $userId
	 * @param string $domain
	 * @param \OCP\IConfig|null $config
	 * @param array $headers Additional CORS headers to merge when setting
	 *
	 * Format of $headers:
	 * Array [
	 *     "Access-Control-Allow-Headers": ["a", "b", "c"],
	 *     "Access-Control-Allow-Origin": ["a", "b", "c"],
	 *     "Access-Control-Allow-Methods": ["a", "b", "c"]
	 * ]
	 */
	public static function setCorsHeaders($response, ?string $userId, string $domain, $config = null, array|null $methods = null) {
		if (is_null($config)) {
			$config = \OC::$server->getConfig();
		}

		// first check if any of the global CORS domains matches
		$globalAllowedDomains = $config->getSystemValue('cors.allowed-domains', []);
		$isCorsRequest = (\is_array($globalAllowedDomains) && \in_array($domain, $globalAllowedDomains, true));
		// check if user defined CORS domains are enabled
		$isUserCorsEnabled = $config->getSystemValueBool('cors.allow-user-domains', false);

		// if not a global CORS domain, but user defined ones are enabled, check if one matches
		if (!$isCorsRequest && $isUserCorsEnabled && $userId !== null) {
			$allowedDomains = \json_decode($config->getUserValue($userId, 'core', 'cors.allowed-domains'), true);
			$isCorsRequest = (\is_array($allowedDomains) && \in_array($domain, $allowedDomains, true));
		}

		// Global or user domain matches so set headers
		if ($isCorsRequest) {
			$allHeaders = [
				'Access-Control-Allow-Origin' => [$domain],
				'Access-Control-Allow-Headers' => self::getAllowedCorsHeaders($config),
				'Access-Control-Expose-Headers' => self::getExposeCorsHeaders(),
				'Access-Control-Allow-Methods' => $methods ?? self::getAllowedCorsMethods(),
				// Indicate that the response might change depending on the origin
				'Vary' => ['Origin'],
			];

			foreach ($allHeaders as $key => $value) {
				$response->addHeader($key, \join(',', $value));
			}
		}
	}

	/**
	 * This function adds the CORS headers for all domains
	 *
	 * @param \OCP\AppFramework\Http\Response|Sabre\HTTP\ResponseInterface $response
	 * @param \OCP\IConfig|null $config
	 * @param array $headers Additional cors headers to merge when setting
	 *
	 * Format of $headers:
	 * Array [
	 *     "Access-Control-Allow-Headers": ["a", "b", "c"],
	 *     "Access-Control-Allow-Origin": ["a", "b", "c"],
	 *     "Access-Control-Allow-Methods": ["a", "b", "c"]
	 * ]
	 *
	 * @return void
	 */
	public static function setOptionsRequestHeaders($response, \OCP\IConfig $config = null, ?array $methods = null) {
		$allHeaders = [
			'Access-Control-Allow-Headers' => self::getAllowedCorsHeaders($config),
			'Access-Control-Allow-Origin' => ['*'],
			'Access-Control-Allow-Methods' => $methods ?? self::getAllowedCorsMethods(),
		];

		foreach ($allHeaders as $key => $value) {
			$response->addHeader($key, \join(',', $value));
		}
	}

	/**
	 * This are the allowed methods a browser can use from javascript code.
	 *
	 * @return string[]
	 */
	private static function getAllowedCorsMethods() {
		return [
			'GET',
			'OPTIONS',
			'POST',
			'PUT',
			'DELETE',
			'MKCOL',
			'PROPFIND',
			'PATCH',
			'PROPPATCH',
			'REPORT',
			'SEARCH',
			'COPY',
			'MOVE',
			'HEAD',
		];
	}

	/**
	 * These are the header which a browser can access from javascript code.
	 * Simple headers are always accessible.
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Expose-Headers
	 *
	 * @return array
	 */
	private static function getExposeCorsHeaders() {
		return [
			'Content-Location',
			'DAV',
			'ETag',
			'Link',
			'Lock-Token',
			'OC-ETag',
			'OC-Checksum',
			'OC-FileId',
			'OC-JobStatus-Location',
			'OC-RequestAppPassword',
			'Vary',
			'Webdav-Location',
			'X-Sabre-Status',
		];
	}

	/**
	 * These are the headers the browser is allowed to ask for in a CORS request.
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Headers
	 *
	 * @param \OCP\IConfig $config
	 * @return array|mixed
	 */
	private static function getAllowedCorsHeaders(\OCP\IConfig $config = null) {
		if ($config === null) {
			$config = \OC::$server->getConfig();
		}
		$allowedDefaultHeaders = [
			// own headers
			'OC-Checksum',
			'OC-Total-Length',
			'OCS-APIREQUEST',
			'X-OC-Mtime',
			'OC-RequestAppPassword',
			// as used in sabre
			'Accept',
			'Authorization',
			'Brief',
			'Content-Length',
			'Content-Range',
			'Content-Type',
			'Date',
			'Depth',
			'Destination',
			'Host',
			'If',
			'If-Match',
			'If-Modified-Since',
			'If-None-Match',
			'If-Range',
			'If-Unmodified-Since',
			'Location',
			'Lock-Token',
			'Overwrite',
			'Prefer',
			'Range',
			'Schedule-Reply',
			'Timeout',
			'User-Agent',
			'X-Expected-Entity-Length',
			// generally used headers in core
			'Accept-Language',
			'Access-Control-Request-Method',
			'Access-Control-Allow-Origin',
			'Cache-Control',
			'ETag',
			'OC-Autorename',
			'OC-CalDav-Import',
			'OC-Chunked',
			'OC-Etag',
			'OC-FileId',
			'OC-LazyOps',
			'OC-Total-File-Length',
			'OC-Total-Length',
			'Origin',
			'X-Request-ID',
			'X-Requested-With'
		];
		$corsAllowedHeaders = $config->getSystemValue('cors.allowed-headers', []);
		$corsAllowedHeaders = \array_merge($corsAllowedHeaders, $allowedDefaultHeaders);
		$corsAllowedHeaders = \array_unique(\array_values($corsAllowedHeaders));
		return $corsAllowedHeaders;
	}
}
