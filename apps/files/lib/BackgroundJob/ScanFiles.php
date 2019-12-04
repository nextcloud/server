<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\Files\BackgroundJob;

use OC\Files\Utils\Scanner;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;

/**
 * Class ScanFiles is a background job used to run the file scanner over the user
 * accounts to ensure integrity of the file cache.
 *
 * @package OCA\Files\BackgroundJob
 */
class ScanFiles extends \OC\BackgroundJob\TimedJob {
	/** @var IConfig */
	private $config;
	/** @var IUserManager */
	private $userManager;
	/** @var IDBConnection */
	private $dbConnection;
	/** @var ILogger */
	private $logger;
	/** Amount of users that should get scanned per execution */
	const USERS_PER_SESSION = 500;

	/**
	 * @param IConfig|null $config
	 * @param IUserManager|null $userManager
	 * @param IDBConnection|null $dbConnection
	 * @param ILogger|null $logger
	 */
	public function __construct(IConfig $config = null,
								IUserManager $userManager = null,
								IDBConnection $dbConnection = null,
								ILogger $logger = null) {
		// Run once per 10 minutes
		$this->setInterval(60 * 10);

		if (is_null($userManager) || is_null($config)) {
			$this->fixDIForJobs();
		} else {
			$this->config = $config;
			$this->userManager = $userManager;
			$this->logger = $logger;
		}
	}

	protected function fixDIForJobs() {
		$this->config = \OC::$server->getConfig();
		$this->userManager = \OC::$server->getUserManager();
		$this->logger = \OC::$server->getLogger();
	}

	/**
	 * @param IUser $user
	 */
	protected function runScanner(IUser $user) {
		try {
			$scanner = new Scanner(
					$user->getUID(),
					$this->dbConnection,
					$this->logger
			);
			$scanner->backgroundScan('');
		} catch (\Exception $e) {
			$this->logger->logException($e, ['app' => 'files']);
		}
		\OC_Util::tearDownFS();
	}

	/**
	 * @param $argument
	 * @throws \Exception
	 */
	protected function run($argument) {
		if ($this->config->getSystemValueBool('files_no_background_scan', false)) {
			return;
		}
		
		$offset = $this->config->getAppValue('files', 'cronjob_scan_files', 0);
		$users = $this->userManager->search('', self::USERS_PER_SESSION, $offset);
		if (!count($users)) {
			// No users found, reset offset and retry
			$offset = 0;
			$users = $this->userManager->search('', self::USERS_PER_SESSION);
		}

		$offset += self::USERS_PER_SESSION;
		$this->config->setAppValue('files', 'cronjob_scan_files', $offset);

		foreach ($users as $user) {
			$this->runScanner($user);
		}
	}
}
