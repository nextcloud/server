<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
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

namespace OC\Cache;

class FileGlobal {
	static protected function getCacheDir() {
		$cache_dir = get_temp_dir().'/owncloud-' . \OC_Util::getInstanceId().'/';
		if (!is_dir($cache_dir)) {
			mkdir($cache_dir);
		}
		return $cache_dir;
	}

	protected function fixKey($key) {
		return str_replace('/', '_', $key);
	}

	/**
	 * @param string $key
	 */
	public function get($key) {
		$key = $this->fixKey($key);
		if ($this->hasKey($key)) {
			$cache_dir = self::getCacheDir();
			return file_get_contents($cache_dir.$key);
		}
		return null;
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	public function set($key, $value, $ttl=0) {
		$key = $this->fixKey($key);
		$cache_dir = self::getCacheDir();
		if ($cache_dir and file_put_contents($cache_dir.$key, $value)) {
			if ($ttl === 0) {
				$ttl = 86400; // 60*60*24
			}
			return touch($cache_dir.$key, time() + $ttl);
		}
		return false;
	}

	public function hasKey($key) {
		$key = $this->fixKey($key);
		$cache_dir = self::getCacheDir();
		if ($cache_dir && is_file($cache_dir.$key) && is_readable($cache_dir.$key)) {
			$mtime = filemtime($cache_dir.$key);
			if ($mtime < time()) {
				unlink($cache_dir.$key);
				return false;
			}
			return true;
		}
		return false;
	}

	public function remove($key) {
		$cache_dir = self::getCacheDir();
		if(!$cache_dir) {
			return false;
		}
		$key = $this->fixKey($key);
		return unlink($cache_dir.$key);
	}

	public function clear($prefix='') {
		$cache_dir = self::getCacheDir();
		$prefix = $this->fixKey($prefix);
		if($cache_dir and is_dir($cache_dir)) {
			$dh=opendir($cache_dir);
			if(is_resource($dh)) {
				while (($file = readdir($dh)) !== false) {
					if($file!='.' and $file!='..' and ($prefix==='' || strpos($file, $prefix) === 0)) {
						unlink($cache_dir.$file);
					}
				}
			}
		}
	}
}
