<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OCP\Cache\CappedMemoryCache;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\PreConditionNotMetException;

/**
 * Class to combine all the configuration options ownCloud offers
 */
class AllConfig implements IConfig {
	private ?IDBConnection $connection = null;

	/**
	 * 3 dimensional array with the following structure:
	 * [ $userId =>
	 *     [ $appId =>
	 *         [ $key => $value ]
	 *     ]
	 * ]
	 *
	 * database table: preferences
	 *
	 * methods that use this:
	 *   - setUserValue
	 *   - getUserValue
	 *   - getUserKeys
	 *   - deleteUserValue
	 *   - deleteAllUserValues
	 *   - deleteAppFromAllUsers
	 *
	 * @var CappedMemoryCache $userCache
	 */
	private CappedMemoryCache $userCache;

	public function __construct(
		private SystemConfig $systemConfig
	) {
		$this->userCache = new CappedMemoryCache();
	}

	/**
	 * TODO - FIXME This fixes an issue with base.php that cause cyclic
	 * dependencies, especially with autoconfig setup
	 *
	 * Replace this by properly injected database connection. Currently the
	 * base.php triggers the getDatabaseConnection too early which causes in
	 * autoconfig setup case a too early distributed database connection and
	 * the autoconfig then needs to reinit all already initialized dependencies
	 * that use the database connection.
	 *
	 * otherwise a SQLite database is created in the wrong directory
	 * because the database connection was created with an uninitialized config
	 */
	private function fixDIInit() {
		if ($this->connection === null) {
			$this->connection = \OC::$server->get(IDBConnection::class);
		}
	}

	public function setSystemValues(array $configs) {
		$this->systemConfig->setValues($configs);
	}

	public function setSystemValue($key, $value) {
		$this->systemConfig->setValue($key, $value);
	}

	public function getSystemValue($key, $default = '') {
		return $this->systemConfig->getValue($key, $default);
	}

	public function getSystemValueBool(string $key, bool $default = false): bool {
		return (bool)$this->getSystemValue($key, $default);
	}

	public function getSystemValueInt(string $key, int $default = 0): int {
		return (int)$this->getSystemValue($key, $default);
	}

	public function getSystemValueString(string $key, string $default = ''): string {
		return (string)$this->getSystemValue($key, $default);
	}

	public function getFilteredSystemValue($key, $default = '') {
		return $this->systemConfig->getFilteredValue($key, $default);
	}

	public function deleteSystemValue($key) {
		$this->systemConfig->deleteValue($key);
	}

	public function getAppKeys($appName) {
		return \OC::$server->get(AppConfig::class)->getKeys($appName);
	}

	public function setAppValue($appName, $key, $value) {
		\OC::$server->get(AppConfig::class)->setValue($appName, $key, $value);
	}

	public function getAppValue($appName, $key, $default = '') {
		return \OC::$server->get(AppConfig::class)->getValue($appName, $key, $default);
	}

	public function deleteAppValue($appName, $key) {
		\OC::$server->get(AppConfig::class)->deleteKey($appName, $key);
	}

	public function deleteAppValues($appName) {
		\OC::$server->get(AppConfig::class)->deleteApp($appName);
	}


	public function setUserValue($userId, $appName, $key, $value, $preCondition = null) {
		if (!is_int($value) && !is_float($value) && !is_string($value)) {
			throw new \UnexpectedValueException('Only integers, floats and strings are allowed as value');
		}

		// TODO - FIXME
		$this->fixDIInit();

		if ($appName === 'settings' && $key === 'email') {
			$value = strtolower((string)$value);
		}

		$prevValue = $this->getUserValue($userId, $appName, $key, null);

		if ($prevValue !== null) {
			if ($prevValue === (string)$value) {
				return;
			} elseif ($preCondition !== null && $prevValue !== (string)$preCondition) {
				throw new PreConditionNotMetException();
			} else {
				$qb = $this->connection->getQueryBuilder();
				$qb->update('preferences')
					->set('configvalue', $qb->createNamedParameter($value))
					->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId)))
					->andWhere($qb->expr()->eq('appid', $qb->createNamedParameter($appName)))
					->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key)));
				$qb->executeStatement();

				$this->userCache[$userId][$appName][$key] = (string)$value;
				return;
			}
		}

		$preconditionArray = [];
		if (isset($preCondition)) {
			$preconditionArray = [
				'configvalue' => $preCondition,
			];
		}

		$this->connection->setValues('preferences', [
			'userid' => $userId,
			'appid' => $appName,
			'configkey' => $key,
		], [
			'configvalue' => $value,
		], $preconditionArray);

		// only add to the cache if we already loaded data for the user
		if (isset($this->userCache[$userId])) {
			if (!isset($this->userCache[$userId][$appName])) {
				$this->userCache[$userId][$appName] = [];
			}
			$this->userCache[$userId][$appName][$key] = (string)$value;
		}
	}

	public function getUserValue($userId, $appName, $key, $default = '') {
		$data = $this->getAllUserValues($userId);
		if (isset($data[$appName][$key])) {
			return $data[$appName][$key];
		} else {
			return $default;
		}
	}

	public function getUserKeys($userId, $appName) {
		$data = $this->getAllUserValues($userId);
		if (isset($data[$appName])) {
			return array_map('strval', array_keys($data[$appName]));
		} else {
			return [];
		}
	}

	public function deleteUserValue($userId, $appName, $key) {
		// TODO - FIXME
		$this->fixDIInit();

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preferences')
			->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('appid', $qb->createNamedParameter($appName, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key, IQueryBuilder::PARAM_STR)))
			->executeStatement();

		if (isset($this->userCache[$userId][$appName])) {
			unset($this->userCache[$userId][$appName][$key]);
		}
	}

	public function deleteAllUserValues($userId) {
		// TODO - FIXME
		$this->fixDIInit();
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preferences')
			->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->executeStatement();

		unset($this->userCache[$userId]);
	}

	public function deleteAppFromAllUsers($appName) {
		// TODO - FIXME
		$this->fixDIInit();

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($appName, IQueryBuilder::PARAM_STR)))
			->executeStatement();

		foreach ($this->userCache as &$userCache) {
			unset($userCache[$appName]);
		}
	}

	public function getAllUserValues(?string $userId): array {
		if (isset($this->userCache[$userId])) {
			return $this->userCache[$userId];
		}
		if ($userId === null || $userId === '') {
			$this->userCache[''] = [];
			return $this->userCache[''];
		}

		// TODO - FIXME
		$this->fixDIInit();

		$data = [];

		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select('appid', 'configkey', 'configvalue')
			->from('preferences')
			->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->executeQuery();
		while ($row = $result->fetch()) {
			$appId = $row['appid'];
			if (!isset($data[$appId])) {
				$data[$appId] = [];
			}
			$data[$appId][$row['configkey']] = $row['configvalue'];
		}
		$this->userCache[$userId] = $data;
		return $data;
	}

	public function getUserValueForUsers($appName, $key, $userIds) {
		// TODO - FIXME
		$this->fixDIInit();

		if (empty($userIds) || !is_array($userIds)) {
			return [];
		}

		$chunkedUsers = array_chunk($userIds, 50, true);

		$qb = $this->connection->getQueryBuilder();
		$qb->select('userid', 'configvalue')
			->from('preferences')
			->where($qb->expr()->eq('appid', $qb->createParameter('appName')))
			->andWhere($qb->expr()->eq('configkey', $qb->createParameter('configKey')))
			->andWhere($qb->expr()->in('userid', $qb->createParameter('userIds')));

		$userValues = [];
		foreach ($chunkedUsers as $chunk) {
			$qb->setParameter('appName', $appName, IQueryBuilder::PARAM_STR);
			$qb->setParameter('configKey', $key, IQueryBuilder::PARAM_STR);
			$qb->setParameter('userIds', $chunk, IQueryBuilder::PARAM_STR_ARRAY);
			$result = $qb->executeQuery();

			while ($row = $result->fetch()) {
				$userValues[$row['userid']] = $row['configvalue'];
			}
		}

		return $userValues;
	}

	public function getUsersForUserValue($appName, $key, $value) {
		// TODO - FIXME
		$this->fixDIInit();

		$qb = $this->connection->getQueryBuilder();
		$configValueColumn = ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE)
			? $qb->expr()->castColumn('configvalue', IQueryBuilder::PARAM_STR)
			: 'configvalue';
		$result = $qb->select('userid')
			->from('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($appName, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq(
				$configValueColumn,
				$qb->createNamedParameter($value, IQueryBuilder::PARAM_STR))
			)->orderBy('userid')
			->executeQuery();

		$userIDs = [];
		while ($row = $result->fetch()) {
			$userIDs[] = $row['userid'];
		}

		return $userIDs;
	}

	/**
	 * Determines the users that have the given value set for a specific app-key-pair
	 *
	 * @param string $appName the app to get the user for
	 * @param string $key the key to get the user for
	 * @param string $value the value to get the user for
	 * @return list<string> of user IDs
	 */
	public function getUsersForUserValueCaseInsensitive($appName, $key, $value) {
		// TODO - FIXME
		$this->fixDIInit();

		if ($appName === 'settings' && $key === 'email') {
			// Email address is always stored lowercase in the database
			return $this->getUsersForUserValue($appName, $key, strtolower($value));
		}

		$qb = $this->connection->getQueryBuilder();
		$configValueColumn = ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE)
			? $qb->expr()->castColumn('configvalue', IQueryBuilder::PARAM_STR)
			: 'configvalue';

		$result = $qb->select('userid')
			->from('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($appName, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq(
				$qb->func()->lower($configValueColumn),
				$qb->createNamedParameter(strtolower($value), IQueryBuilder::PARAM_STR))
			)->orderBy('userid')
			->executeQuery();

		$userIDs = [];
		while ($row = $result->fetch()) {
			$userIDs[] = $row['userid'];
		}

		return $userIDs;
	}

	public function getSystemConfig() {
		return $this->systemConfig;
	}
}
