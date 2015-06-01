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

use OC\Command\QueueBus;
use OC\Files\Filesystem;
use OCP\Security\ISecureRandom;

abstract class TestCase extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \OC\Command\QueueBus
	 */
	private $commandBus;

	protected function setUp() {
		// overwrite the command bus with one we can run ourselves
		$this->commandBus = new QueueBus();
		\OC::$server->registerService('AsyncCommandBus', function () {
			return $this->commandBus;
		});
	}

	protected function tearDown() {
		$hookExceptions = \OC_Hook::$thrownExceptions;
		\OC_Hook::$thrownExceptions = [];
		\OC::$server->getLockingProvider()->releaseAll();
		if(!empty($hookExceptions)) {
			throw $hookExceptions[0];
		}
	}

	/**
	 * Returns a unique identifier as uniqid() is not reliable sometimes
	 *
	 * @param string $prefix
	 * @param int $length
	 * @return string
	 */
	protected static function getUniqueID($prefix = '', $length = 13) {
		return $prefix . \OC::$server->getSecureRandom()->getLowStrengthGenerator()->generate(
			$length,
			// Do not use dots and slashes as we use the value for file names
			ISecureRandom::CHAR_DIGITS . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER
		);
	}

	public static function tearDownAfterClass() {
		$dataDir = \OC::$server->getConfig()->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data-autotest');

		self::tearDownAfterClassCleanFileMapper($dataDir);
		self::tearDownAfterClassCleanStorages();
		self::tearDownAfterClassCleanFileCache();
		self::tearDownAfterClassCleanStrayDataFiles($dataDir);
		self::tearDownAfterClassCleanStrayHooks();

		parent::tearDownAfterClass();
	}

	/**
	 * Remove all entries from the files map table
	 *
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
	 *
	 * @throws \OC\DatabaseException
	 */
	static protected function tearDownAfterClassCleanStorages() {
		$sql = 'DELETE FROM `*PREFIX*storages`';
		$query = \OC_DB::prepare($sql);
		$query->execute();
	}

	/**
	 * Remove all entries from the filecache table
	 *
	 * @throws \OC\DatabaseException
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
			'owncloud.log' => true,
			'owncloud.db' => true,
			'.ocdata' => true,
			'..' => true,
			'.' => true,
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
				} else {
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
	 * Login and setup FS as a given user,
	 * sets the given user as the current user.
	 *
	 * @param string $user user id or empty for a generic FS
	 */
	static protected function loginAsUser($user = '') {
		self::logout();
		\OC\Files\Filesystem::tearDown();
		\OC_User::setUserId($user);
		\OC_Util::setupFS($user);
		if (\OC_User::userExists($user)) {
			\OC::$server->getUserFolder($user);
		}
	}

	/**
	 * Logout the current user and tear down the filesystem.
	 */
	static protected function logout() {
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		// needed for fully logout
		\OC::$server->getUserSession()->setUser(null);
	}

	/**
	 * Run all commands pushed to the bus
	 */
	protected function runCommands() {
		// get the user for which the fs is setup
		$view = Filesystem::getView();
		if ($view) {
			list(, $user) = explode('/', $view->getRoot());
		} else {
			$user = null;
		}

		\OC_Util::tearDownFS(); // command cant reply on the fs being setup
		$this->commandBus->run();
		\OC_Util::tearDownFS();

		if ($user) {
			\OC_Util::setupFS($user);
		}
	}
}
