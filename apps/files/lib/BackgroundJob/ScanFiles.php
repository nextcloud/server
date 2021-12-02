<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files\BackgroundJob;

use OC\Files\Utils\Scanner;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;

/**
 * Class ScanFiles is a background job used to run the file scanner over the user
 * accounts to ensure integrity of the file cache.
 *
 * @package OCA\Files\BackgroundJob
 */
class ScanFiles extends \OC\BackgroundJob\TimedJob {
	/** @var IConfig */
	private $config;
	/** @var IEventDispatcher */
	private $dispatcher;
	/** @var ILogger */
	private $logger;
	private $connection;

	/** Amount of users that should get scanned per execution */
	public const USERS_PER_SESSION = 500;

	/**
	 * @param IConfig $config
	 * @param IEventDispatcher $dispatcher
	 * @param ILogger $logger
	 * @param IDBConnection $connection
	 */
	public function __construct(
		IConfig $config,
		IEventDispatcher $dispatcher,
		ILogger $logger,
		IDBConnection $connection
	) {
		// Run once per 10 minutes
		$this->setInterval(60 * 10);

		$this->config = $config;
		$this->dispatcher = $dispatcher;
		$this->logger = $logger;
		$this->connection = $connection;
	}

	/**
	 * @param string $user
	 */
	protected function runScanner(string $user) {
		try {
			$scanner = new Scanner(
				$user,
				null,
				$this->dispatcher,
				$this->logger
			);
			$scanner->backgroundScan('');
		} catch (\Exception $e) {
			$this->logger->logException($e, ['app' => 'files']);
		}
		\OC_Util::tearDownFS();
	}

	/**
	 * Find a storage which have unindexed files and return a user with access to the storage
	 *
	 * @return string|false
	 */
	private function getUserToScan() {
		$query = $this->connection->getQueryBuilder();
		$query->select('user_id')
			->from('filecache', 'f')
			->innerJoin('f', 'mounts', 'm', $query->expr()->eq('storage_id', 'storage'))
			->where($query->expr()->lt('size', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->gt('parent', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)))
			->setMaxResults(1);

		return $query->execute()->fetchOne();
	}

	/**
	 * @param $argument
	 * @throws \Exception
	 */
	protected function run($argument) {
		if ($this->config->getSystemValueBool('files_no_background_scan', false)) {
			return;
		}

		$usersScanned = 0;
		$lastUser = '';
		$user = $this->getUserToScan();
		while ($user && $usersScanned < self::USERS_PER_SESSION && $lastUser !== $user) {
			$this->runScanner($user);
			$lastUser = $user;
			$user = $this->getUserToScan();
			$usersScanned += 1;
		}

		if ($lastUser === $user) {
			$this->logger->warning("User $user still has unscanned files after running background scan, background scan might be stopped prematurely");
		}
	}
}
