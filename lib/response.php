<?php
/**
 * Copyright (c) 2011 Bart Visscher bartv@thisnet.nl
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Response {
	const STATUS_FOUND = 304;
	const STATUS_NOT_MODIFIED = 304;
	const STATUS_TEMPORARY_REDIRECT = 307;
	const STATUS_NOT_FOUND = 404;

	/**
	* @brief Enable response caching by sending correct HTTP headers
	* @param $cache_time time to cache the response
	*  >0		cache time in seconds
	*  0 and <0	enable default browser caching
	*  null		cache indefinitly
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
	* @brief disable browser caching
	* @see enableCaching with cache_time = 0
	*/
	static public function disableCaching() {
		self::enableCaching(0);
	}

	/**
	* @brief Set response status
	* @param $status a HTTP status code, see also the STATUS constants
	*/
	static public function setStatus($status) {
		$protocol = $_SERVER['SERVER_PROTOCOL'];
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
		}
		header($protocol.' '.$status);
	}

	/**
	* @brief Send redirect response
	* @param $location to redirect to
	*/
	static public function redirect($location) {
		self::setStatus(self::STATUS_TEMPORARY_REDIRECT);
		header('Location: '.$location);
	}

	/**
	* @brief Set reponse expire time
	* @param $expires date-time when the response expires
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
	* @param $etag token to use for modification check
	*/
	static public function setETagHeader($etag) {
		if (empty($etag)) {
			return;
		}
		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
		    trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
			self::setStatus(self::STATUS_NOT_MODIFIED);
			exit;
		}
		header('ETag: "'.$etag.'"');
	}

	/**
	* Checks and set Last-Modified header, when the request matches sends a
	* 'not modified' response
	* @param $lastModified time when the reponse was last modified
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
	* @brief Send file as response, checking and setting caching headers
	* @param $filepath of file to send
	*/
	static public function sendFile($filepath) {
		$fp = fopen($filepath, 'rb');
		if ($fp) {
			self::setLastModifiedHeader(filemtime($filepath));
			self::setETagHeader(md5_file($filepath));

			header('Content-Length: '.filesize($filepath));
			fpassthru($fp);
		}
		else {
			self::setStatus(self::STATUS_NOT_FOUND);
		}
	}
}
