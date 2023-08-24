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
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Class ScanFiles is a background job used to run the file scanner over the user
 * accounts to ensure integrity of the file cache.
 *
 * @package OCA\Files\BackgroundJob
 */
class ScanFiles extends TimedJob {
	private IConfig $config;
	private IEventDispatcher $dispatcher;
	private LoggerInterface $logger;
	private IDBConnection $connection;

	/** Amount of users that should get scanned per execution */
	public const USERS_PER_SESSION = 500;

	public function __construct(
		IConfig $config,
		IEventDispatcher $dispatcher,
		LoggerInterface $logger,
		IDBConnection $connection,
		ITimeFactory $time
	) {
		parent::__construct($time);
		// Run once per 10 minutes
		$this->setInterval(60 * 10);

		$this->config = $config;
		$this->dispatcher = $dispatcher;
		$this->logger = $logger;
		$this->connection = $connection;
	}

	protected function runScanner(string $user): void {
		try {
			$scanner = new Scanner(
				$user,
				null,
				$this->dispatcher,
				$this->logger
			);
			$scanner->backgroundScan('');
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e, 'app' => 'files']);
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

		$usersScanned = [];
		$storageScanned = [];

		$query = $this->connection->getQueryBuilder();
		$query->select('storage_id', 'user_id')->from('mounts');
		$storageUsers = $query->executeQuery()->fetchAll();
		$storageUsers = array_column($storageUsers, 'user_id', 'storage_id');
		$mountedStorageIds = array_keys($storageUsers);

		$query = $this->connection->getQueryBuilder();
		$query->selectDistinct('storage')->from('filecache', 'f')
			->where($query->expr()->lt('size', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->gt('parent', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)));
		$storageIds = $query->executeQuery()->fetchAll();
		$storageIds = array_column($storageIds, 'storage');

		$scanningStrageIds = array_intersect($mountedStorageIds, $storageIds);

		foreach ($scanningStrageIds as $scanningStrageId) {
			if (count($usersScanned) >= self::USERS_PER_SESSION) {
				break;
			}
			$storageScanned[] = $scanningStrageId;
			if (in_array($storageUsers[$scanningStrageId], $usersScanned)) {
				continue;
			}
			$this->runScanner($storageUsers[$scanningStrageId]);
			$usersScanned[] = $storageUsers[$scanningStrageId];
		}

		if ($this->config->getSystemValue('loglevel') > ILogger::WARN) {
			return;
		}

		$query = $this->connection->getQueryBuilder();
		$query->selectDistinct('storage')->from('filecache', 'f')
			->where($query->expr()->lt('size', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->gt('parent', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->in('storage', $query->createNamedParameter($storageScanned, IQueryBuilder::PARAM_INT_ARRAY)));
		$unscannedStorageIds = $query->executeQuery()->fetchAll();
		$unscannedStorageIds = array_column($unscannedStorageIds, 'storage');

		foreach ($unscannedStorageIds as $unscannedStorageId) {
			if (!isset($storageUsers[$unscannedStorageId])) {
				continue;
			}
			$user = $storageUsers[$unscannedStorageId];
			$userIndex = array_search($user, $usersScanned);
			if ($userIndex !== false) {
				unset($usersScanned[$userIndex]);
				$this->logger->warning("User $user still has unscanned files after running background scan, background scan might be stopped prematurely");
			}
		}

	}
}
