<?php
/**
 * Copyright (c) 2013 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

/**
 * ownCloud backend for external storage based on DAV backend.
 *
 * The ownCloud URL consists of three parts:
 * http://%host/%context/remote.php/webdav/%root
 *
 */
class OwnCloud extends \OC\Files\Storage\DAV{
	const OC_URL_SUFFIX = 'remote.php/webdav';

	public function __construct($params) {
		// extract context path from host if specified
		// (owncloud install path on host)
		$host = $params['host'];
		// strip protocol
		if (substr($host, 0, 8) == "https://") {
			$host = substr($host, 8);
			$params['secure'] = true;
		} else if (substr($host, 0, 7) == "http://") {
			$host = substr($host, 7);
			$params['secure'] = false;
		}
		$contextPath = '';
		$hostSlashPos = strpos($host, '/');
		if ($hostSlashPos !== false){
			$contextPath = substr($host, $hostSlashPos);
			$host = substr($host, 0, $hostSlashPos);
		}

		if (substr($contextPath , 1) !== '/'){
			$contextPath .= '/';
		}

		if (isset($params['root'])){
			$root = $params['root'];
			if (substr($root, 1) !== '/'){
				$root = '/' . $root;
			}
		}
		else{
			$root = '/';
		}

		$params['host'] = $host;
		$params['root'] = $contextPath . self::OC_URL_SUFFIX . $root;

		parent::__construct($params);
	}
}
