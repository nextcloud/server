<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\Repair;

use OC\Files\Cache\Storage;
use OC\RepairException;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RepairLegacyStorages implements IRepairStep{
	/**
	 * @var \OCP\IConfig
	 */
	protected $config;

	/**
	 * @var \OCP\IDBConnection
	 */
	protected $connection;

	protected $findStorageInCacheStatement;
	protected $renameStorageStatement;

	/**
	 * @param \OCP\IConfig $config
	 * @param \OCP\IDBConnection $connection
	 */
	public function __construct($config, $connection) {
		$this->connection = $connection;
		$this->config = $config;

		$this->findStorageInCacheStatement = $this->connection->prepare(
			'SELECT DISTINCT `storage` FROM `*PREFIX*filecache`'
			. ' WHERE `storage` in (?, ?)'
		);
		$this->renameStorageStatement = $this->connection->prepare(
			'UPDATE `*PREFIX*storages`'
			. ' SET `id` = ?'
			. ' WHERE `id` = ?'
		);
	}

	public function getName() {
		return 'Repair legacy storages';
	}

	/**
	 * Extracts the user id	from a legacy storage id
	 *
	 * @param string $storageId legacy storage id in the
	 * format "local::/path/to/datadir/userid"
	 * @return string user id extracted from the storage id
	 */
	private function extractUserId($storageId) {
		$storageId = rtrim($storageId, '/');
		$pos = strrpos($storageId, '/');
		return substr($storageId, $pos + 1);
	}

	/**
	 * Fix the given legacy storage by renaming the old id
	 * to the new id. If the new id already exists, whichever
	 * storage that has data in the file cache will be used.
	 * If both have data, nothing will be done and false is
	 * returned.
	 *
	 * @param string $oldId old storage id
	 * @param int $oldNumericId old storage numeric id
	 * @param string $userId
	 * @return bool true if fixed, false otherwise
	 * @throws RepairException
	 */
	private function fixLegacyStorage($oldId, $oldNumericId, $userId = null) {
		// check whether the new storage already exists
		if (is_null($userId)) {
			$userId = $this->extractUserId($oldId);
		}
		$newId = 'home::' . $userId;

		// check if target id already exists
		$newNumericId = Storage::getNumericStorageId($newId);
		if (!is_null($newNumericId)) {
			$newNumericId = (int)$newNumericId;
			// try and resolve the conflict
			// check which one of "local::" or "home::" needs to be kept
			$this->findStorageInCacheStatement->execute(array($oldNumericId, $newNumericId));
			$row1 = $this->findStorageInCacheStatement->fetch();
			$row2 = $this->findStorageInCacheStatement->fetch();
			$this->findStorageInCacheStatement->closeCursor();
			if ($row2 !== false) {
				// two results means both storages have data, not auto-fixable
				throw new RepairException(
					'Could not automatically fix legacy storage '
					. '"' . $oldId . '" => "' . $newId . '"'
					. ' because they both have data.'
				);
			}
			if ($row1 === false || (int)$row1['storage'] === $oldNumericId) {
				// old storage has data, then delete the empty new id
				$toDelete = $newId;
			} else if ((int)$row1['storage'] === $newNumericId) {
				// new storage has data, then delete the empty old id
				$toDelete = $oldId;
			} else {
				// unknown case, do not continue
				return false;
			}

			// delete storage including file cache
			Storage::remove($toDelete);

			// if we deleted the old id, the new id will be used
			// automatically
			if ($toDelete === $oldId) {
				// nothing more to do
				return true;
			}
		}

		// rename old id to new id
		$newId = Storage::adjustStorageId($newId);
		$oldId = Storage::adjustStorageId($oldId);
		$rowCount = $this->renameStorageStatement->execute(array($newId, $oldId));
		$this->renameStorageStatement->closeCursor();
		return ($rowCount === 1);
	}

	/**
	 * Converts legacy home storage ids in the format
	 * "local::/data/dir/path/userid/" to the new format "home::userid"
	 */
	public function run(IOutput $out) {
		// only run once
		if ($this->config->getAppValue('core', 'repairlegacystoragesdone') === 'yes') {
			return;
		}

		$dataDir = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data/');
		$dataDir = rtrim($dataDir, '/') . '/';
		$dataDirId = 'local::' . $dataDir;

		$count = 0;
		$hasWarnings = false;

		$this->connection->beginTransaction();

		// note: not doing a direct UPDATE with the REPLACE function
		// because regexp search/extract is needed and it is not guaranteed
		// to work on all database types
		$sql = 'SELECT `id`, `numeric_id` FROM `*PREFIX*storages`'
			. ' WHERE `id` LIKE ?'
			. ' ORDER BY `id`';
		$result = $this->connection->executeQuery($sql, array($this->connection->escapeLikeParameter($dataDirId) . '%'));

		while ($row = $result->fetch()) {
			$currentId = $row['id'];
			// one entry is the datadir itself
			if ($currentId === $dataDirId) {
				continue;
			}

			try {
				if ($this->fixLegacyStorage($currentId, (int)$row['numeric_id'])) {
					$count++;
				}
			}
			catch (RepairException $e) {
				$hasWarnings = true;
				$out->warning('Could not repair legacy storage ' . $currentId . ' automatically.');
			}
		}

		// check for md5 ids, not in the format "prefix::"
		$sql = 'SELECT COUNT(*) AS "c" FROM `*PREFIX*storages`'
			. ' WHERE `id` NOT LIKE \'%::%\'';
		$result = $this->connection->executeQuery($sql);
		$row = $result->fetch();

		// find at least one to make sure it's worth
		// querying the user list
		if ((int)$row['c'] > 0) {
			$userManager = \OC::$server->getUserManager();

			// use chunks to avoid caching too many users in memory
			$limit = 30;
			$offset = 0;

			do {
				// query the next page of users
				$results = $userManager->search('', $limit, $offset);
				$storageIds = array();
				foreach ($results as $uid => $userObject) {
					$storageId = $dataDirId . $uid . '/';
					if (strlen($storageId) <= 64) {
						// skip short storage ids as they were handled in the previous section
						continue;
					}
					$storageIds[$uid] = $storageId;
				}

				if (count($storageIds) > 0) {
					// update the storages of these users
					foreach ($storageIds as $uid => $storageId) {
						$numericId = Storage::getNumericStorageId($storageId);
						try {
							if (!is_null($numericId) && $this->fixLegacyStorage($storageId, (int)$numericId)) {
								$count++;
							}
						}
						catch (RepairException $e) {
							$hasWarnings = true;
							$out->warning('Could not repair legacy storage ' . $storageId . ' automatically.');
						}
					}
				}
				$offset += $limit;
			} while (count($results) >= $limit);
		}

		$out->info('Updated ' . $count . ' legacy home storage ids');

		$this->connection->commit();

		if ($hasWarnings) {
			$out->warning('Some legacy storages could not be repaired. Please manually fix them then re-run ./occ maintenance:repair');
		} else {
			// if all were done, no need to redo the repair during next upgrade
			$this->config->setAppValue('core', 'repairlegacystoragesdone', 'yes');
		}
	}
}
