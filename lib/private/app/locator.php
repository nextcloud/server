<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OC\App;

/**
 * Find an app in the configured app directories
 *
 * @package OC\App
 */
class Locator {
	/**
	 * @var string[]
	 */
	private $cache = [];

	private $appRoots = [];

	/**
	 * Locator constructor.
	 *
	 * @param array $appRoots
	 */
	public function __construct(array $appRoots) {
		$this->appRoots = $appRoots;
	}

	/**
	 * Get the absolute path of an app
	 *
	 * @param string $appId
	 * @return bool|string
	 */
	public function getAppPath($appId) {
		if (($dir = $this->getDirectoryForApp($appId)) != false) {
			return $dir['path'] . '/' . $appId;
		}
		return false;
	}

	/**
	 * Get the directory an app is in
	 *
	 * @param string $appId
	 * @return bool|string
	 */
	public function getDirectoryForApp($appId) {
		$sanitizedAppId = \OC_App::cleanAppId($appId);
		if ($sanitizedAppId !== $appId) {
			return false;
		}

		if (isset($this->cache[$appId])) {
			return $this->cache[$appId];
		}

		$possibleApps = array();
		foreach ($this->appRoots as $dir) {
			if (file_exists($dir['path'] . '/' . $appId)) {
				$possibleApps[] = $dir;
			}
		}

		if (empty($possibleApps)) {
			return false;
		} elseif (count($possibleApps) === 1) {
			$dir = array_shift($possibleApps);
			$this->cache[$appId] = $dir;
		} else {
			$latestMtime = 0;
			foreach ($possibleApps as $possibleApp) {
				$mtime = filemtime($possibleApp['path']);
				if ($mtime > $latestMtime) {
					$this->cache[$appId] = $possibleApp;
					$latestMtime = $mtime;
				}
			}
		}
		return $this->cache[$appId];
	}
}
