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

use OCP\IUser;
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
	 * @var IUserManager
	 */
	private $userManager;

	public function __construct(IUserManager $userManager = null, Expiration $expiration = null) {
		// Run once per 30 minutes
		$this->setInterval(60 * 30);

		if (is_null($expiration) || is_null($userManager)) {
			$this->fixDIForJobs();
		} else {
			$this->expiration = $expiration;
			$this->userManager = $userManager;
		}
	}

	protected function fixDIForJobs() {
		$application = new Application();
		$this->expiration = $application->getContainer()->query('Expiration');
		$this->userManager = \OC::$server->getUserManager();
	}

	protected function run($argument) {
		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if (!$maxAge) {
			return;
		}

		$this->callForAllUsers(function(IUser $user) {
			$uid = $user->getUID();
			if (!$this->setupFS($uid)) {
				return;
			}
			Storage::expireOlderThanMaxForUser($uid);
		});
	}

	/**
	 * Act on behalf on trash item owner
	 * @param string $user
	 * @return boolean
	 */
	protected function setupFS($user) {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($user);

		// Check if this user has a versions directory
		$view = new \OC\Files\View('/' . $user);
		if (!$view->is_dir('/files_versions')) {
			return false;
		}

		return true;
	}

	/**
	 * The callback is executed for each user on each backend.
	 * If the callback returns false no further users will be retrieved.
	 *
	 * @param \Closure $callback
	 * @param string $search
	 */
	protected function callForAllUsers(\Closure $callback, $search = '') {
		foreach ($this->userManager->getBackends() as $backend) {
			$limit = 500;
			$offset = 0;
			do {
				$users = $backend->getUsers($search, $limit, $offset);
				foreach ($users as $user) {
					$user = $this->userManager->get($user);
					if (is_null($user)) {
						continue;
					}
					$return = $callback($user);
					if ($return === false) {
						break;
					}
				}
				$offset += $limit;
			} while (count($users) >= $limit);
		}
	}
}
