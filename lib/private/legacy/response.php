<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

class OC_Response {
	const STATUS_FOUND = 304;
	const STATUS_NOT_MODIFIED = 304;
	const STATUS_TEMPORARY_REDIRECT = 307;
	const STATUS_BAD_REQUEST = 400;
	const STATUS_NOT_FOUND = 404;
	const STATUS_INTERNAL_SERVER_ERROR = 500;
	const STATUS_SERVICE_UNAVAILABLE = 503;

	/**
	* Enable response caching by sending correct HTTP headers
	* @param integer $cache_time time to cache the response
	*  >0		cache time in seconds
	*  0 and <0	enable default browser caching
	*  null		cache indefinitely
	*/
	static public function enableCaching($cache_time = null) {
		if (is_numeric($cache_time)) {
			header('Pragma: public');// enable caching in IE
			if ($cache_time > 0) {
				self::setExpiresHeader('PT'.$cache_time.'S');
				header('Cache-Control: max-age='.$cache_time.', must-revalidate');
			}
			else {
				self::setExpiresHeader(0);
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			}
		}
		else {
			header('Cache-Control: cache');
			header('Pragma: cache');
		}

	}

	/**
	* disable browser caching
	* @see enableCaching with cache_time = 0
	*/
	static public function disableCaching() {
		self::enableCaching(0);
	}

	/**
	* Set response status
	* @param int $status a HTTP status code, see also the STATUS constants
	*/
	static public function setStatus($status) {
		$protocol = \OC::$server->getRequest()->getHttpProtocol();
		switch($status) {
			case self::STATUS_NOT_MODIFIED:
				$status = $status . ' Not Modified';
				break;
			case self::STATUS_TEMPORARY_REDIRECT:
				if ($protocol == 'HTTP/1.1') {
					$status = $status . ' Temporary Redirect';
					break;
				} else {
					$status = self::STATUS_FOUND;
					// fallthrough
				}
			case self::STATUS_FOUND;
				$status = $status . ' Found';
				break;
			case self::STATUS_NOT_FOUND;
				$status = $status . ' Not Found';
				break;
			case self::STATUS_INTERNAL_SERVER_ERROR;
				$status = $status . ' Internal Server Error';
				break;
			case self::STATUS_SERVICE_UNAVAILABLE;
				$status = $status . ' Service Unavailable';
				break;
		}
		header($protocol.' '.$status);
	}

	/**
	* Send redirect response
	* @param string $location to redirect to
	*/
	static public function redirect($location) {
		self::setStatus(self::STATUS_TEMPORARY_REDIRECT);
		header('Location: '.$location);
	}

	/**
	* Set response expire time
	* @param string|DateTime $expires date-time when the response expires
	*  string for DateInterval from now
	*  DateTime object when to expire response
	*/
	static public function setExpiresHeader($expires) {
		if (is_string($expires) && $expires[0] == 'P') {
			$interval = $expires;
			$expires = new DateTime('now');
			$expires->add(new DateInterval($interval));
		}
		if ($expires instanceof DateTime) {
			$expires->setTimezone(new DateTimeZone('GMT'));
			$expires = $expires->format(DateTime::RFC2822);
		}
		header('Expires: '.$expires);
	}

	/**
	* Checks and set ETag header, when the request matches sends a
	* 'not modified' response
	* @param string $etag token to use for modification check
	*/
	static public function setETagHeader($etag) {
		if (empty($etag)) {
			return;
		}
		$etag = '"'.$etag.'"';
		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
		    trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
			self::setStatus(self::STATUS_NOT_MODIFIED);
			exit;
		}
		header('ETag: '.$etag);
	}

	/**
	* Checks and set Last-Modified header, when the request matches sends a
	* 'not modified' response
	* @param int|DateTime|string $lastModified time when the response was last modified
	*/
	static public function setLastModifiedHeader($lastModified) {
		if (empty($lastModified)) {
			return;
		}
		if (is_int($lastModified)) {
			$lastModified = gmdate(DateTime::RFC2822, $lastModified);
		}
		if ($lastModified instanceof DateTime) {
			$lastModified = $lastModified->format(DateTime::RFC2822);
		}
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
		    trim($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified) {
			self::setStatus(self::STATUS_NOT_MODIFIED);
			exit;
		}
		header('Last-Modified: '.$lastModified);
	}

	/**
	 * Sets the content disposition header (with possible workarounds)
	 * @param string $filename file name
	 * @param string $type disposition type, either 'attachment' or 'inline'
	 */
	static public function setContentDispositionHeader( $filename, $type = 'attachment' ) {
		if (\OC::$server->getRequest()->isUserAgent(
			[
				\OC\AppFramework\Http\Request::USER_AGENT_IE,
				\OC\AppFramework\Http\Request::USER_AGENT_ANDROID_MOBILE_CHROME,
				\OC\AppFramework\Http\Request::USER_AGENT_FREEBOX,
			])) {
			header( 'Content-Disposition: ' . rawurlencode($type) . '; filename="' . rawurlencode( $filename ) . '"' );
		} else {
			header( 'Content-Disposition: ' . rawurlencode($type) . '; filename*=UTF-8\'\'' . rawurlencode( $filename )
												 . '; filename="' . rawurlencode( $filename ) . '"' );
		}
	}

	/**
	 * Sets the content length header (with possible workarounds)
	 * @param string|int|float $length Length to be sent
	 */
	static public function setContentLengthHeader($length) {
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
	 * Send file as response, checking and setting caching headers
	 * @param string $filepath of file to send
	 * @deprecated 8.1.0 - Use \OCP\AppFramework\Http\StreamResponse or another AppFramework controller instead
	 */
	static public function sendFile($filepath) {
		$fp = fopen($filepath, 'rb');
		if ($fp) {
			self::setLastModifiedHeader(filemtime($filepath));
			self::setETagHeader(md5_file($filepath));

			self::setContentLengthHeader(filesize($filepath));
			fpassthru($fp);
		}
		else {
			self::setStatus(self::STATUS_NOT_FOUND);
		}
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
			. 'script-src \'self\' \'unsafe-eval\'; '
			. 'style-src \'self\' \'unsafe-inline\'; '
			. 'frame-src *; '
			. 'img-src * data: blob:; '
			. 'font-src \'self\' data:; '
			. 'media-src *; ' 
			. 'connect-src *';
		header('Content-Security-Policy:' . $policy);

		// Send fallback headers for installations that don't have the possibility to send
		// custom headers on the webserver side
		if(getenv('modHeadersAvailable') !== 'true') {
			header('X-XSS-Protection: 1; mode=block'); // Enforce browser based XSS filters
			header('X-Content-Type-Options: nosniff'); // Disable sniffing the content type for IE
			header('X-Frame-Options: Sameorigin'); // Disallow iFraming from other domains
			header('X-Robots-Tag: none'); // https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag
			header('X-Download-Options: noopen'); // https://msdn.microsoft.com/en-us/library/jj542450(v=vs.85).aspx
			header('X-Permitted-Cross-Domain-Policies: none'); // https://www.adobe.com/devnet/adobe-media-server/articles/cross-domain-xml-for-streaming.html
		}
	}

}
