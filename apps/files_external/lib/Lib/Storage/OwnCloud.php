<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
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

namespace OCA\Files_External\Lib\Storage;

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

		if (substr($contextPath, -1) !== '/'){
			$contextPath .= '/';
		}

		if (isset($params['root'])){
			$root = $params['root'];
			if (substr($root, 0, 1) !== '/'){
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
