<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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

namespace OCA\Files_Versions\BackgroundJob;

use OCP\IDBConnection;
use OCP\IUserManager;
use OCA\Files_Versions\AppInfo\Application;
use OCA\Files_Versions\Storage;
use OCA\Files_Versions\Expiration;

class ExpireVersions extends \OC\BackgroundJob\TimedJob {

	const ITEMS_PER_SESSION = 1000;

	/**
	 * @var Expiration
	 */
	private $expiration;

	/**
	 * @var IDBConnection
	 */
	private $dbConnection;
	
	/**
	 * @var IUserManager
	 */
	private $userManager;

	public function __construct(IDBConnection $dbConnection = null, IUserManager $userManager = null, Expiration $expiration = null) {
		// Run once per 30 minutes
		$this->setInterval(60 * 30);

		if (is_null($expiration) || is_null($userManager) || is_null($dbConnection)) {
			$this->fixDIForJobs();
		} else {
			$this->dbConnection = $dbConnection;
			$this->expiration = $expiration;
			$this->userManager = $userManager;
		}
	}

	protected function fixDIForJobs() {
		$application = new Application();
		$this->dbConnection = \OC::$server->getDatabaseConnection();
		$this->expiration = $application->getContainer()->query('Expiration');
		$this->userManager = \OC::$server->getUserManager();
	}

	protected function run($argument) {
		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if (!$maxAge) {
			return;
		}

		$users = $this->userManager->search('');
		$isFSready = false;
		foreach ($users as $user) {
			$uid = $user->getUID();
			if (!$isFSready) {
				if (!$this->setupFS($uid)) {
					continue;
				}
				$isFSready = true;
			}
			Storage::expireOlderThan($uid);
		}

		\OC_Util::tearDownFS();
	}

	/**
	 * Act on behalf on trash item owner
	 * @param string $user
	 * @return boolean
	 */
	private function setupFS($user){
		if (!$this->userManager->userExists($user)) {
			return false;
		}

		\OC_Util::tearDownFS();
		\OC_Util::setupFS($user);

		return true;
	}
}