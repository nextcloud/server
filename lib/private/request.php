<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Request {

	const USER_AGENT_IE = '/MSIE/';
	// Android Chrome user agent: https://developers.google.com/chrome/mobile/docs/user-agent
	const USER_AGENT_ANDROID_MOBILE_CHROME = '#Android.*Chrome/[.0-9]*#';
	const USER_AGENT_FREEBOX = '#^Mozilla/5\.0$#';
	const REGEX_LOCALHOST = '/^(127\.0\.0\.1|localhost)$/';
	static protected $reqId;

	/**
	 * Returns the remote address, if the connection came from a trusted proxy and `forwarded_for_headers` has been configured
	 * then the IP address specified in this header will be returned instead.
	 * Do always use this instead of $_SERVER['REMOTE_ADDR']
	 * @return string IP address
	 */
	public static function getRemoteAddress() {
		$remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		$trustedProxies = \OC::$server->getConfig()->getSystemValue('trusted_proxies', array());

		if(is_array($trustedProxies) && in_array($remoteAddress, $trustedProxies)) {
			$forwardedForHeaders = \OC::$server->getConfig()->getSystemValue('forwarded_for_headers', array());

			foreach($forwardedForHeaders as $header) {
				if (array_key_exists($header, $_SERVER) === true) {
					foreach (explode(',', $_SERVER[$header]) as $IP) {
						$IP = trim($IP);
						if (filter_var($IP, FILTER_VALIDATE_IP) !== false) {
							return $IP;
						}
					}
				}
			}
		}

		return $remoteAddress;
	}

	/**
	 * Returns an ID for the request, value is not guaranteed to be unique and is mostly meant for logging
	 * @return string
	 */
	public static function getRequestID() {
		if(self::$reqId === null) {
			self::$reqId = hash('md5', microtime().\OC::$server->getSecureRandom()->getLowStrengthGenerator()->generate(20));
		}
		return self::$reqId;
	}

	/**
	 * Check overwrite condition
	 * @param string $type
	 * @return bool
	 */
	private static function isOverwriteCondition($type = '') {
		$regex = '/' . OC_Config::getValue('overwritecondaddr', '')  . '/';
		return $regex === '//' or preg_match($regex, $_SERVER['REMOTE_ADDR']) === 1
			or ($type !== 'protocol' and OC_Config::getValue('forcessl', false));
	}

	/**
	 * Strips a potential port from a domain (in format domain:port)
	 * @param $host
	 * @return string $host without appended port
	 */
	public static function getDomainWithoutPort($host) {
		$pos = strrpos($host, ':');
		if ($pos !== false) {
			$port = substr($host, $pos + 1);
			if (is_numeric($port)) {
				$host = substr($host, 0, $pos);
			}
		}
		return $host;
	}

	/**
	 * Checks whether a domain is considered as trusted from the list
	 * of trusted domains. If no trusted domains have been configured, returns
	 * true.
	 * This is used to prevent Host Header Poisoning.
	 * @param string $domainWithPort
	 * @return bool true if the given domain is trusted or if no trusted domains
	 * have been configured
	 */
	public static function isTrustedDomain($domainWithPort) {
		// Extract port from domain if needed
		$domain = self::getDomainWithoutPort($domainWithPort);

		// FIXME: Empty config array defaults to true for now. - Deprecate this behaviour with ownCloud 8.
		$trustedList = \OC::$server->getConfig()->getSystemValue('trusted_domains', array());
		if (empty($trustedList)) {
			return true;
		}

		// FIXME: Workaround for older instances still with port applied. Remove for ownCloud 9.
		if(in_array($domainWithPort, $trustedList)) {
			return true;
		}

		// Always allow access from localhost
		if (preg_match(self::REGEX_LOCALHOST, $domain) === 1) {
			return true;
		}

		return in_array($domain, $trustedList);
	}

	/**
	 * Returns the unverified server host from the headers without checking
	 * whether it is a trusted domain
	 * @return string the server host
	 *
	 * Returns the server host, even if the website uses one or more
	 * reverse proxies
	 */
	public static function insecureServerHost() {
		$host = null;
		if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
			if (strpos($_SERVER['HTTP_X_FORWARDED_HOST'], ",") !== false) {
				$parts = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
				$host = trim(current($parts));
			} else {
				$host = $_SERVER['HTTP_X_FORWARDED_HOST'];
			}
		} else {
			if (isset($_SERVER['HTTP_HOST'])) {
				$host = $_SERVER['HTTP_HOST'];
			} else if (isset($_SERVER['SERVER_NAME'])) {
				$host = $_SERVER['SERVER_NAME'];
			}
		}
		return $host;
	}

	/**
	 * Returns the overwritehost setting from the config if set and
	 * if the overwrite condition is met
	 * @return string|null overwritehost value or null if not defined or the defined condition
	 * isn't met
	 */
	public static function getOverwriteHost() {
		if(OC_Config::getValue('overwritehost', '') !== '' and self::isOverwriteCondition()) {
			return OC_Config::getValue('overwritehost');
		}
		return null;
	}

	/**
	 * Returns the server host from the headers, or the first configured
	 * trusted domain if the host isn't in the trusted list
	 * @return string the server host
	 *
	 * Returns the server host, even if the website uses one or more
	 * reverse proxies
	 */
	public static function serverHost() {
		if (OC::$CLI && defined('PHPUNIT_RUN')) {
			return 'localhost';
		}

		// overwritehost is always trusted
		$host = self::getOverwriteHost();
		if ($host !== null) {
			return $host;
		}

		// get the host from the headers
		$host = self::insecureServerHost();

		// Verify that the host is a trusted domain if the trusted domains
		// are defined
		// If no trusted domain is provided the first trusted domain is returned
		if (self::isTrustedDomain($host)) {
			return $host;
		} else {
			$trustedList = \OC_Config::getValue('trusted_domains', array(''));
			return $trustedList[0];
		}
	}

	/**
	* Returns the server protocol
	* @return string the server protocol
	*
	* Returns the server protocol. It respects reverse proxy servers and load balancers
	*/
	public static function serverProtocol() {
		if(OC_Config::getValue('overwriteprotocol', '') !== '' and self::isOverwriteCondition('protocol')) {
			return OC_Config::getValue('overwriteprotocol');
		}
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
			$proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
			// Verify that the protocol is always HTTP or HTTPS
			// default to http if an invalid value is provided
			return $proto === 'https' ? 'https' : 'http';
		}
		if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
			return 'https';
		}
		return 'http';
	}

	/**
	 * Returns the request uri
	 * @return string the request uri
	 *
	 * Returns the request uri, even if the website uses one or more
	 * reverse proxies
	 * @return string
	 */
	public static function requestUri() {
		$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		if (OC_Config::getValue('overwritewebroot', '') !== '' and self::isOverwriteCondition()) {
			$uri = self::scriptName() . substr($uri, strlen($_SERVER['SCRIPT_NAME']));
		}
		return $uri;
	}

	/**
	 * Returns the script name
	 * @return string the script name
	 *
	 * Returns the script name, even if the website uses one or more
	 * reverse proxies
	 */
	public static function scriptName() {
		$name = $_SERVER['SCRIPT_NAME'];
		$overwriteWebRoot = OC_Config::getValue('overwritewebroot', '');
		if ($overwriteWebRoot !== '' and self::isOverwriteCondition()) {
			$serverroot = str_replace("\\", '/', substr(__DIR__, 0, -strlen('lib/private/')));
			$suburi = str_replace("\\", "/", substr(realpath($_SERVER["SCRIPT_FILENAME"]), strlen($serverroot)));
			$name = '/' . ltrim($overwriteWebRoot . $suburi, '/');
		}
		return $name;
	}

	/**
	 * get Path info from request
	 * @return string Path info or false when not found
	 */
	public static function getPathInfo() {
		if (array_key_exists('PATH_INFO', $_SERVER)) {
			$path_info = $_SERVER['PATH_INFO'];
		}else{
			$path_info = self::getRawPathInfo();
			// following is taken from \Sabre\DAV\URLUtil::decodePathSegment
			$path_info = rawurldecode($path_info);
			$encoding = mb_detect_encoding($path_info, array('UTF-8', 'ISO-8859-1'));

			switch($encoding) {

				case 'ISO-8859-1' :
					$path_info = utf8_encode($path_info);

			}
			// end copy
		}
		return $path_info;
	}

	/**
	 * get Path info from request, not urldecoded
	 * @throws Exception
	 * @return string Path info or false when not found
	 */
	public static function getRawPathInfo() {
		$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		// remove too many leading slashes - can be caused by reverse proxy configuration
		if (strpos($requestUri, '/') === 0) {
			$requestUri = '/' . ltrim($requestUri, '/');
		}

		$requestUri = preg_replace('%/{2,}%', '/', $requestUri);

		// Remove the query string from REQUEST_URI
		if ($pos = strpos($requestUri, '?')) {
			$requestUri = substr($requestUri, 0, $pos);
		}

		$scriptName = $_SERVER['SCRIPT_NAME'];
		$path_info = $requestUri;

		// strip off the script name's dir and file name
		list($path, $name) = \Sabre\DAV\URLUtil::splitPath($scriptName);
		if (!empty($path)) {
			if( $path === $path_info || strpos($path_info, $path.'/') === 0) {
				$path_info = substr($path_info, strlen($path));
			} else {
				throw new Exception("The requested uri($requestUri) cannot be processed by the script '$scriptName')");
			}
		}
		if (strpos($path_info, '/'.$name) === 0) {
			$path_info = substr($path_info, strlen($name) + 1);
		}
		if (strpos($path_info, $name) === 0) {
			$path_info = substr($path_info, strlen($name));
		}
		if($path_info === '/'){
			return '';
		} else {
			return $path_info;
		}
	}

	/**
	 * Check if the requester sent along an mtime
	 * @return false or an mtime
	 */
	static public function hasModificationTime () {
		if (isset($_SERVER['HTTP_X_OC_MTIME'])) {
			return $_SERVER['HTTP_X_OC_MTIME'];
		} else {
			return false;
		}
	}

	/**
	 * Checks whether the user agent matches a given regex
	 * @param string|array $agent agent name or array of agent names
	 * @return boolean true if at least one of the given agent matches,
	 * false otherwise
	 */
	static public function isUserAgent($agent) {
		if (!is_array($agent)) {
			$agent = array($agent);
		}
		foreach ($agent as $regex) {
			if (preg_match($regex, $_SERVER['HTTP_USER_AGENT'])) {
				return true;
			}
		}
		return false;
	}
}
