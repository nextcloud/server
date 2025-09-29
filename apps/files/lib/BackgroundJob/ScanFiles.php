<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files\BackgroundJob;

use OC\Files\Utils\Scanner;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Class ScanFiles is a background job used to run the file scanner over the user
 * accounts to ensure integrity of the file cache.
 *
 * @package OCA\Files\BackgroundJob
 */
class ScanFiles extends TimedJob {
	/** Amount of users that should get scanned per execution */
	public const USERS_PER_SESSION = 500;

	public function __construct(
		private IConfig $config,
		private IEventDispatcher $dispatcher,
		private LoggerInterface $logger,
		private IDBConnection $connection,
		ITimeFactory $time,
	) {
		parent::__construct($time);
		// Run once per 10 minutes
		$this->setInterval(60 * 10);
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
	 * Find a storage which have unindexed files and return a user with access to the storage
	 *
	 * @return string|false
	 */
	private function getUserToScan() {
		if ($this->connection->getShardDefinition('filecache')) {
			// for sharded filecache, the "LIMIT" from the normal query doesn't work

			// first we try it with a "LEFT JOIN" on mounts, this is fast, but might return a storage that isn't mounted.
			// we also ask for up to 10 results from different storages to increase the odds of finding a result that is mounted
			$query = $this->connection->getQueryBuilder();
			$query->select('m.user_id')
				->from('filecache', 'f')
				->leftJoin('f', 'mounts', 'm', $query->expr()->eq('m.storage_id', 'f.storage'))
				->where($query->expr()->eq('f.size', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)))
				->andWhere($query->expr()->gt('f.parent', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)))
				->setMaxResults(10)
				->groupBy('f.storage')
				->runAcrossAllShards();

			$result = $query->executeQuery();
			while ($res = $result->fetch()) {
				if ($res['user_id']) {
					return $res['user_id'];
				}
			}

			// as a fallback, we try a slower approach where we find all mounted storages first
			// this is essentially doing the inner join manually
			$storages = $this->getAllMountedStorages();

			$query = $this->connection->getQueryBuilder();
			$query->select('m.user_id')
				->from('filecache', 'f')
				->leftJoin('f', 'mounts', 'm', $query->expr()->eq('m.storage_id', 'f.storage'))
				->where($query->expr()->eq('f.size', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)))
				->andWhere($query->expr()->gt('f.parent', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)))
				->andWhere($query->expr()->in('f.storage', $query->createNamedParameter($storages, IQueryBuilder::PARAM_INT_ARRAY)))
				->setMaxResults(1)
				->runAcrossAllShards();
			return $query->executeQuery()->fetchOne();
		} else {
			$query = $this->connection->getQueryBuilder();
			$query->select('m.user_id')
				->from('filecache', 'f')
				->innerJoin('f', 'mounts', 'm', $query->expr()->eq('m.storage_id', 'f.storage'))
				->where($query->expr()->eq('f.size', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)))
				->andWhere($query->expr()->gt('f.parent', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)))
				->setMaxResults(1)
				->runAcrossAllShards();

			return $query->executeQuery()->fetchOne();
		}
	}

	private function getAllMountedStorages(): array {
		$query = $this->connection->getQueryBuilder();
		$query->selectDistinct('storage_id')
			->from('mounts');
		return $query->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
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
