<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Request {
	/**
	 * @brief Returns the server host
	 * @returns the server host
	 *
	 * Returns the server host, even if the website uses one or more
	 * reverse proxies
	 */
	public static function serverHost() {
		if(OC::$CLI) {
			return 'localhost';
		}
		if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
			if (strpos($_SERVER['HTTP_X_FORWARDED_HOST'], ",") !== false) {
				$host = trim(array_pop(explode(",", $_SERVER['HTTP_X_FORWARDED_HOST'])));
			}
			else{
				$host=$_SERVER['HTTP_X_FORWARDED_HOST'];
			}
		}
		else{
			$host = $_SERVER['HTTP_HOST'];
		}
		return $host;
	}


	/**
	* @brief Returns the server protocol
	* @returns the server protocol
	*
	* Returns the server protocol. It respects reverse proxy servers and load balancers
	*/
	public static function serverProtocol() {
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
			$proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
		}else{
			if(isset($_SERVER['HTTPS']) and !empty($_SERVER['HTTPS']) and ($_SERVER['HTTPS']!='off')) {
				$proto = 'https';
			}else{
				$proto = 'http';
			}
		}
		return $proto;
	}

	/**
	 * @brief get Path info from request
	 * @returns string Path info or false when not found
	 */
	public static function getPathInfo() {
		if (array_key_exists('PATH_INFO', $_SERVER)) {
			$path_info = $_SERVER['PATH_INFO'];
		}else{
			$path_info = substr($_SERVER['REQUEST_URI'], strlen($_SERVER['SCRIPT_NAME']));
			// following is taken from Sabre_DAV_URLUtil::decodePathSegment
			$path_info = rawurldecode($path_info);
			$encoding = mb_detect_encoding($path_info, array('UTF-8','ISO-8859-1'));

			switch($encoding) {

			    case 'ISO-8859-1' :
				$path_info = utf8_encode($path_info);

			}
			// end copy
		}
		return $path_info;
	}

	/**
	 * @brief Check if this is a no-cache request
	 * @returns true for no-cache
	 */
	static public function isNoCache() {
		if (!isset($_SERVER['HTTP_CACHE_CONTROL'])) {
			return false;
		}
		return $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache';
	}

	/**
	 * @brief Check if the requestor understands gzip
	 * @returns true for gzip encoding supported
	 */
	static public function acceptGZip() {
		if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
			return false;
		}
		$HTTP_ACCEPT_ENCODING = $_SERVER["HTTP_ACCEPT_ENCODING"];
		if( strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false )
			return 'x-gzip';
		else if( strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false )
			return 'gzip';
		return false;
	}
}
