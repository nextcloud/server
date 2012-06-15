<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Request {
	static public function isNoCache() {
		if (!isset($_SERVER['HTTP_CACHE_CONTROL'])) {
			return false;
		}
		return $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache';
	}

	static public function acceptGZip() {
		$HTTP_ACCEPT_ENCODING = $_SERVER["HTTP_ACCEPT_ENCODING"];
		if( strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false )
			return 'x-gzip';
		else if( strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false )
			return 'gzip';
		return false;
	}
}
