<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
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

use OC\BackgroundJob\Job;
use OCP\IConfig;

class FileGlobalGC extends Job {
	// only do cleanup every 5 minutes
	const CLEANUP_TTL_SEC = 300;

	public function run($argument) {
		$this->gc(\OC::$server->getConfig(), $this->getCacheDir());
	}

	protected function getCacheDir() {
		return get_temp_dir() . '/owncloud-' . \OC_Util::getInstanceId() . '/';
	}

	/**
	 * @param string $cacheDir
	 * @param int $now
	 * @return string[]
	 */
	public function getExpiredPaths($cacheDir, $now) {
		$files = scandir($cacheDir);
		$files = array_filter($files, function ($file) {
			return $file != '.' and $file != '..';
		});
		$paths = array_map(function ($file) use ($cacheDir) {
			return $cacheDir . $file;
		}, $files);
		return array_values(array_filter($paths, function ($path) use ($now) {
			return is_file($path) and (filemtime($path) < $now);
		}));
	}

	/**
	 * @param \OCP\IConfig $config
	 * @param string $cacheDir
	 */
	public function gc(IConfig $config, $cacheDir) {
		$lastRun = $config->getAppValue('core', 'global_cache_gc_lastrun', 0);
		$now = time();
		if (($now - $lastRun) < self::CLEANUP_TTL_SEC) {
			return;
		}
		$config->setAppValue('core', 'global_cache_gc_lastrun', $now);
		if (!is_dir($cacheDir)) {
			return;
		}
		$paths = $this->getExpiredPaths($cacheDir, $now);
		array_walk($paths, function($file) {
    			if (file_exists($file)) {
        			unlink($file);
    			}
		});
	}
}
