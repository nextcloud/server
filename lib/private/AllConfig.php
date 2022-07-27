<?php
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
	private function fixDIInit() {
		if ($this->connection === null) {
			$this->connection = \OC::$server->get(IDBConnection::class);
		}
	}

	/**
	 * Sets and deletes system wide values
	 *
	 * @param array $configs Associative array with `key => value` pairs
	 *                       If value is null, the config key will be deleted
	 */
	public function setSystemValues(array $configs) {
		$this->systemConfig->setValues($configs);
	}

	/**
	 * Sets a new system wide value
	 *
	 * @param string $key the key of the value, under which will be saved
	 * @param mixed $value the value that should be stored
	 */
	public function setSystemValue($key, $value) {
		$this->systemConfig->setValue($key, $value);
	}

	/**
	 * Looks up a system wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param mixed $default the default value to be returned if the value isn't set
	 * @return mixed the value or $default
	 */
	public function getSystemValue($key, $default = '') {
		return $this->systemConfig->getValue($key, $default);
	}

	/**
	 * Looks up a boolean system wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param bool $default the default value to be returned if the value isn't set
	 *
	 * @return bool
	 *
	 * @since 16.0.0
	 */
	public function getSystemValueBool(string $key, bool $default = false): bool {
		return (bool) $this->getSystemValue($key, $default);
	}

	/**
	 * Looks up an integer system wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param int $default the default value to be returned if the value isn't set
	 *
	 * @return int
	 *
	 * @since 16.0.0
	 */
	public function getSystemValueInt(string $key, int $default = 0): int {
		return (int) $this->getSystemValue($key, $default);
	}

	/**
	 * Looks up a string system wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param string $default the default value to be returned if the value isn't set
	 *
	 * @return string
	 *
	 * @since 16.0.0
	 */
	public function getSystemValueString(string $key, string $default = ''): string {
		return (string) $this->getSystemValue($key, $default);
	}

	/**
	 * Looks up a system wide defined value and filters out sensitive data
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param mixed $default the default value to be returned if the value isn't set
	 * @return mixed the value or $default
	 */
	public function getFilteredSystemValue($key, $default = '') {
		return $this->systemConfig->getFilteredValue($key, $default);
	}

	/**
	 * Delete a system wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 */
	public function deleteSystemValue($key) {
		$this->systemConfig->deleteValue($key);
	}

	/**
	 * Get all keys stored for an app
	 *
	 * @param string $appName the appName that we stored the value under
	 * @return string[] the keys stored for the app
	 */
	public function getAppKeys($appName) {
		return \OC::$server->get(AppConfig::class)->getKeys($appName);
	}

	/**
	 * Writes a new app wide value
	 *
	 * @param string $appName the appName that we want to store the value under
	 * @param string $key the key of the value, under which will be saved
	 * @param string|float|int $value the value that should be stored
	 */
	public function setAppValue($appName, $key, $value) {
		\OC::$server->get(AppConfig::class)->setValue($appName, $key, $value);
	}

	/**
	 * Looks up an app wide defined value
	 *
	 * @param string $appName the appName that we stored the value under
	 * @param string $key the key of the value, under which it was saved
	 * @param string $default the default value to be returned if the value isn't set
	 * @return string the saved value
	 */
	public function getAppValue($appName, $key, $default = '') {
		return \OC::$server->get(AppConfig::class)->getValue($appName, $key, $default);
	}

	/**
	 * Delete an app wide defined value
	 *
	 * @param string $appName the appName that we stored the value under
	 * @param string $key the key of the value, under which it was saved
	 */
	public function deleteAppValue($appName, $key) {
		\OC::$server->get(AppConfig::class)->deleteKey($appName, $key);
	}

	/**
	 * Removes all keys in appconfig belonging to the app
	 *
	 * @param string $appName the appName the configs are stored under
	 */
	public function deleteAppValues($appName) {
		\OC::$server->get(AppConfig::class)->deleteApp($appName);
	}


	/**
	 * Set a user defined value
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $appName the appName that we want to store the value under
	 * @param string $key the key under which the value is being stored
	 * @param string|float|int $value the value that you want to store
	 * @param string $preCondition only update if the config value was previously the value passed as $preCondition
	 * @throws \OCP\PreConditionNotMetException if a precondition is specified and is not met
	 * @throws \UnexpectedValueException when trying to store an unexpected value
	 */
	public function setUserValue($userId, $appName, $key, $value, $preCondition = null) {
		if (!is_int($value) && !is_float($value) && !is_string($value)) {
			throw new \UnexpectedValueException('Only integers, floats and strings are allowed as value');
		}

		// TODO - FIXME
		$this->fixDIInit();

		if ($appName === 'settings' && $key === 'email') {
			$value = strtolower((string) $value);
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

	/**
	 * Getting a user defined value
	 *
	 * @param ?string $userId the userId of the user that we want to store the value under
	 * @param string $appName the appName that we stored the value under
	 * @param string $key the key under which the value is being stored
	 * @param mixed $default the default value to be returned if the value isn't set
	 * @return string
	 */
	public function getUserValue($userId, $appName, $key, $default = '') {
		$data = $this->getAllUserValues($userId);
		if (isset($data[$appName][$key])) {
			return $data[$appName][$key];
		} else {
			return $default;
		}
	}

	/**
	 * Get the keys of all stored by an app for the user
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $appName the appName that we stored the value under
	 * @return string[]
	 */
	public function getUserKeys($userId, $appName) {
		$data = $this->getAllUserValues($userId);
		if (isset($data[$appName])) {
			return array_keys($data[$appName]);
		} else {
			return [];
		}
	}

	/**
	 * Delete a user value
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $appName the appName that we stored the value under
	 * @param string $key the key under which the value is being stored
	 */
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

	/**
	 * Delete all user values
	 *
	 * @param string $userId the userId of the user that we want to remove all values from
	 */
	public function deleteAllUserValues($userId) {
		// TODO - FIXME
		$this->fixDIInit();
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preferences')
			->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->executeStatement();

		unset($this->userCache[$userId]);
	}

	/**
	 * Delete all user related values of one app
	 *
	 * @param string $appName the appName of the app that we want to remove all values from
	 */
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

	/**
	 * Returns all user configs sorted by app of one user
	 *
	 * @param ?string $userId the user ID to get the app configs from
	 * @psalm-return array<string, array<string, string>>
	 * @return array[] - 2 dimensional array with the following structure:
	 *     [ $appId =>
	 *         [ $key => $value ]
	 *     ]
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
	 * Fetches a mapped list of userId -> value, for a specified app and key and a list of user IDs.
	 *
	 * @param string $appName app to get the value for
	 * @param string $key the key to get the value for
	 * @param array $userIds the user IDs to fetch the values for
	 * @return array Mapped values: userId => value
	 */
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

	/**
	 * Determines the users that have the given value set for a specific app-key-pair
	 *
	 * @param string $appName the app to get the user for
	 * @param string $key the key to get the user for
	 * @param string $value the value to get the user for
	 * @return array of user IDs
	 */
	public function getUsersForUserValue($appName, $key, $value) {
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
	 * Determines the users that have the given value set for a specific app-key-pair
	 *
	 * @param string $appName the app to get the user for
	 * @param string $key the key to get the user for
	 * @param string $value the value to get the user for
	 * @return array of user IDs
	 */
	public function getUsersForUserValueCaseInsensitive($appName, $key, $value) {
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
