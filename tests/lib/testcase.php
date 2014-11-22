<?php
/**
 * ownCloud
 *
 * @author Joas Schilling
 * @copyright 2014 Joas Schilling nickvergessen@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test;

abstract class TestCase extends \PHPUnit_Framework_TestCase {
	/**
	 * Returns a unique identifier as uniqid() is not reliable sometimes
	 *
	 * @param string $prefix
	 * @param int $length
	 * @return string
	 */
	protected function getUniqueID($prefix = '', $length = 13) {
		return $prefix . \OC::$server->getSecureRandom()->getLowStrengthGenerator()->generate(
			$length,
			// Do not use dots and slashes as we use the value for file names
			'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
		);
	}

	public static function tearDownAfterClass() {
		$dataDir = \OC::$server->getConfig()->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data-autotest');

		self::tearDownAfterClassCleanFileMapper($dataDir);
		self::tearDownAfterClassCleanStorages();
		self::tearDownAfterClassCleanFileCache();
		self::tearDownAfterClassCleanStrayDataFiles($dataDir);
		self::tearDownAfterClassCleanStrayHooks();
		self::tearDownAfterClassCleanProxies();

		parent::tearDownAfterClass();
	}

	/**
	 * Remove all entries from the files map table
	 * @param string $dataDir
	 */
	static protected function tearDownAfterClassCleanFileMapper($dataDir) {
		if (\OC_Util::runningOnWindows()) {
			$mapper = new \OC\Files\Mapper($dataDir);
			$mapper->removePath($dataDir, true, true);
		}
	}

	/**
	 * Remove all entries from the storages table
	 * @throws \DatabaseException
	 */
	static protected function tearDownAfterClassCleanStorages() {
		$sql = 'DELETE FROM `*PREFIX*storages`';
		$query = \OC_DB::prepare($sql);
		$query->execute();
	}

	/**
	 * Remove all entries from the filecache table
	 * @throws \DatabaseException
	 */
	static protected function tearDownAfterClassCleanFileCache() {
		$sql = 'DELETE FROM `*PREFIX*filecache`';
		$query = \OC_DB::prepare($sql);
		$query->execute();
	}

	/**
	 * Remove all unused files from the data dir
	 *
	 * @param string $dataDir
	 */
	static protected function tearDownAfterClassCleanStrayDataFiles($dataDir) {
		$knownEntries = array(
			'owncloud.log'	=> true,
			'owncloud.db'	=> true,
			'.ocdata'		=> true,
			'..'			=> true,
			'.'				=> true,
		);

		if ($dh = opendir($dataDir)) {
			while (($file = readdir($dh)) !== false) {
				if (!isset($knownEntries[$file])) {
					self::tearDownAfterClassCleanStrayDataUnlinkDir($dataDir . '/' . $file);
				}
			}
			closedir($dh);
		}
	}

	/**
	 * Recursive delete files and folders from a given directory
	 *
	 * @param string $dir
	 */
	static protected function tearDownAfterClassCleanStrayDataUnlinkDir($dir) {
		if ($dh = @opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if ($file === '..' || $file === '.') {
					continue;
				}
				$path = $dir . '/' . $file;
				if (is_dir($path)) {
					self::tearDownAfterClassCleanStrayDataUnlinkDir($path);
				}
				else {
					@unlink($path);
				}
			}
			closedir($dh);
		}
		@rmdir($dir);
	}

	/**
	 * Clean up the list of hooks
	 */
	static protected function tearDownAfterClassCleanStrayHooks() {
		\OC_Hook::clear();
	}

	/**
	 * Clean up the list of file proxies
	 *
	 * Also reenables file proxies, in case a test disabled them
	 */
	static protected function tearDownAfterClassCleanProxies() {
		\OC_FileProxy::$enabled = true;
		\OC_FileProxy::clearProxies();
	}
}
