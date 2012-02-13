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

	static public function enableCaching() {
		header('Cache-Control: cache');
		header('Pragma: cache');
	}

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
		}
		header($protocol.' '.$status);
	}

	static public function redirect($location) {
		self::setStatus(self::STATUS_TEMPORARY_REDIRECT);
		header('Location: '.$location);
	}

	static public function setExpiresHeader($expires) {
		if (is_string($expires) && $expires[0] == 'P') {
			$interval = $expires;
			$expires = new DateTime('now');
			$expires->add(new DateInterval($interval));
		}
		if ($expires instanceof DateTime) {
			$expires = $expires->format(DateTime::RFC2822);
		}
		header('Expires: '.$expires);
	}

	static public function setETagHeader($etag) {
		if (empty($etag)) {
			return;
		}
		self::enableCaching();
		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
		    trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
			self::setStatus(self::STATUS_NOT_MODIFIED);
			exit;
		}
		header('ETag: '.$etag);
	}

	static public function setLastModifiedHeader($lastModified) {
		if (empty($lastModified)) {
			return;
		}
		if ($lastModified instanceof DateTime) {
			$lastModified = $lastModified->format(DateTime::RFC2822);
		}
		self::enableCaching();
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
		    trim($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified) {
			self::setStatus(self::STATUS_NOT_MODIFIED);
			exit;
		}
		header('Last-Modified: '.$lastModified);
	}
}
