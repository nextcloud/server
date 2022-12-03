<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Loki3000 <github@labcms.ru>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author MichaIng <micha@dietpi.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
	private SystemConfig $systemConfig;
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

	public function __construct(SystemConfig $systemConfig) {
		$this->userCache = new CappedMemoryCache();
		$this->systemConfig = $systemConfig;
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
	private function fixDIInit(): void {
		if ($this->connection === null) {
			$this->connection = \OC::$server->get(IDBConnection::class);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function setSystemValues(array $configs): void {
		$this->systemConfig->setValues($configs);
	}

	/**
	 * @inheritdoc
	 */
	public function setSystemValue(string $key, $value): void {
		$this->systemConfig->setValue($key, $value);
	}

	/**
	 * @inheritdoc
	 */
	public function getSystemValue($key, $default = '') {
		return $this->systemConfig->getValue($key, $default);
	}

	/**
	 * @inheritdoc
	 */
	public function getSystemValueBool(string $key, bool $default = false): bool {
		return (bool) $this->getSystemValue($key, $default);
	}

	/**
	 * @inheritdoc
	 */
	public function getSystemValueInt(string $key, int $default = 0): int {
		return (int) $this->getSystemValue($key, $default);
	}

	/**
	 * @inheritdoc
	 */
	public function getSystemValueString(string $key, string $default = ''): string {
		return (string) $this->getSystemValue($key, $default);
	}

	/**
	 * @inheritdoc
	 */
	public function getFilteredSystemValue(string $key, string $default = ''): string {
		return $this->systemConfig->getFilteredValue($key, $default);
	}

	/**
	 * @inheritdoc
	 */
	public function deleteSystemValue(string $key): void {
		$this->systemConfig->deleteValue($key);
	}

	/**
	 * @inheritdoc
	 */
	public function getAppKeys(string $appName): array {
		return \OC::$server->get(AppConfig::class)->getKeys($appName);
	}

	/**
	 * @inheritdoc
	 */
	public function setAppValue(string $appName, string $key, string $value): void {
		\OC::$server->get(AppConfig::class)->setValue($appName, $key, $value);
	}

	/**
	 * @inheritdoc
	 */
	public function getAppValue(string $appName, string $key, $default = '') {
		return \OC::$server->get(AppConfig::class)->getValue($appName, $key, $default);
	}

	/**
	 * @inheritdoc
	 */
	public function deleteAppValue(string $appName, string $key): void {
		\OC::$server->get(AppConfig::class)->deleteKey($appName, $key);
	}

	/**
	 * @inheritdoc
	 */
	public function deleteAppValues(string $appName): void {
		\OC::$server->get(AppConfig::class)->deleteApp($appName);
	}

	/**
	 * @inheritdoc
	 */
	public function setUserValue(string $userId, string $appName, string $key, string $value, ?string $preCondition = null): void {
		// TODO - FIXME
		$this->fixDIInit();

		if ($appName === 'settings' && $key === 'email') {
			$value = strtolower($value);
		}

		$prevValue = $this->getUserValue($userId, $appName, $key, null);

		if ($prevValue !== null) {
			if ($prevValue === $value) {
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

	/**
	 * @inheritdoc
	 */
	public function getUserValue(?string $userId, string $appName, string $key, ?string $default = ''): ?string {
		$data = $this->getAllUserValues($userId);
		if (isset($data[$appName][$key])) {
			return $data[$appName][$key];
		} else {
			return $default;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getUserKeys(?string $userId, string $appName): array {
		$data = $this->getAllUserValues($userId);
		if (isset($data[$appName])) {
			return array_keys($data[$appName]);
		} else {
			return [];
		}
	}

	/**
	 * @inheritdoc
	 */
	public function deleteUserValue(string $userId, string $appName, string $key): void {
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

	/**
	 * @inheritdoc
	 */
	public function deleteAllUserValues(string $userId): void {
		// TODO - FIXME
		$this->fixDIInit();
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preferences')
			->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->executeStatement();

		unset($this->userCache[$userId]);
	}

	/**
	 * @inheritdoc
	 */
	public function deleteAppFromAllUsers(string $appName): void {
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

	/**
	 * @inheritdoc
	 */
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

	/**
	 * @inheritdoc
	 */
	public function getUserValueForUsers(string $appName, string $key, array $userIds): array {
		// TODO - FIXME
		$this->fixDIInit();

		if (empty($userIds)) {
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

	/**
	 * @inheritdoc
	 */
	public function getUsersForUserValue(string $appName, string $key, string $value): array {
		// TODO - FIXME
		$this->fixDIInit();

		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select('userid')
			->from('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($appName, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq(
				$qb->expr()->castColumn('configvalue', IQueryBuilder::PARAM_STR),
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
	 * @inheritdoc
	 */
	public function getUsersForUserValueCaseInsensitive(string $appName, string $key, string $value): array {
		// TODO - FIXME
		$this->fixDIInit();

		if ($appName === 'settings' && $key === 'email') {
			// Email address is always stored lowercase in the database
			return $this->getUsersForUserValue($appName, $key, strtolower($value));
		}
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select('userid')
			->from('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($appName, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq(
				$qb->func()->lower($qb->expr()->castColumn('configvalue', IQueryBuilder::PARAM_STR)),
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
