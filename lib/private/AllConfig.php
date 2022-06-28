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

use OC\Cache\CappedMemoryCache;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\PreConditionNotMetException;
use Psr\Log\LoggerInterface;

/**
 * Class to combine all the configuration options ownCloud offers
 */
class AllConfig implements IConfig {

	const USER_KEY_LIMIT = 64;
	const APP_KEY_LIMIT = 64;


	private SystemConfig $systemConfig;
	private ?IDBConnection $connection = null;
	private LoggerInterface $logger;

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
	private array $appDefaultValues = [];
	private array $userDefaultValues = [];
	private array $systemDefaultValues = [];


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
	 * @param string $appName
	 * @param array $default
	 */
	public function setAppDefaultValues(string $appName, array $default): void {
		$this->appDefaultValues[$appName] = $default;
	}

	public function setUserDefaultValues(string $appName, array $default): void {
		$this->userDefaultValues[$appName] = $default;
	}

	public function setSystemDefaultValues(array $default): void {
		$this->systemDefaultValues = $default;
	}


	/**
	 * Sets and deletes system wide values
	 *
	 * @param array $configs Associative array with `key => value` pairs
	 *                       If value is null, the config key will be deleted
	 */
	public function setSystemValues(array $configs): void {
		$this->systemConfig->setValues($configs);
	}

	/**
	 * Sets a new system wide value
	 *
	 * @param string $key the key of the value, under which will be saved
	 * @param mixed $value the value that should be stored
	 */
	public function setSystemValue(string $key, $value): void {
		$this->systemConfig->setValue($key, $value);
	}

	public function setSystemValueInt(string $key, int $value): void {
	}

	public function setSystemValueBool(string $key, bool $value): void {
	}

	public function setSystemValueArray(string $key, array $value): void {
	}


	/**
	 * Looks up a system wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param mixed $default the default value to be returned if the value isn't set
	 *
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
		return (bool)$this->getSystemValue($key, $default);
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
		return (int)$this->getSystemValue($key, $default);
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
		return (string)$this->getSystemValue($key, $default);
	}

	/**
	 * Looks up a system wide defined value and filters out sensitive data
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param mixed $default the default value to be returned if the value isn't set
	 *
	 * @return mixed the value or $default
	 */
	public function getFilteredSystemValue(string $key, $default = '') {
		return $this->systemConfig->getFilteredValue($key, $default);
	}

	/**
	 * Delete a system wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 */
	public function deleteSystemValue(string $key) {
		$this->systemConfig->deleteValue($key);
	}

	/**
	 * Get all keys stored for an app
	 *
	 * @param string $appName the appName that we stored the value under
	 *
	 * @return string[] the keys stored for the app
	 */
	public function getAppKeys(string $appName): array {
		return \OC::$server->get(AppConfig::class)->getKeys($appName);
	}

	/**
	 * Writes a new app wide value
	 *
	 * @param string $appName the appName that we want to store the value under
	 * @param string $key the key of the value, under which will be saved
	 * @param string|float|int $value the value that should be stored
	 */
	public function setAppValue(string $appName, string $key, $value): void {
		if (!is_string($value)) {
			\OC::$server->get(LoggerInterface::class)
						->warning(
							'Value used in setAppValue() with config key ' . $key
							. ' is not a string. Please use the suitable method.'
						);
		}

		\OC::$server->get(AppConfig::class)->setValue($appName, $key, $value);
	}

	/**
	 * @param string $appName
	 * @param string $key
	 * @param int $value
	 */
	public function setAppValueInt(string $appName, string $key, int $value): void {
		if (strlen($key) > self::APP_KEY_LIMIT) {
			\OC::$server->get(LoggerInterface::class)
						->warning('key is too long: ' . $key . ' - limit is ' . self::APP_KEY_LIMIT);
		}

		\OC::$server->get(AppConfig::class)->setValueInt($appName, $key, $value);
	}

	/**
	 * @param string $appName
	 * @param string $key
	 * @param bool $value
	 */
	public function setAppValueBool(string $appName, string $key, bool $value): void {
		if (strlen($key) > self::APP_KEY_LIMIT) {
			\OC::$server->get(LoggerInterface::class)
						->warning('key is too long: ' . $key . ' - limit is ' . self::APP_KEY_LIMIT);
		}

		\OC::$server->get(AppConfig::class)->setValueBool($appName, $key, $value);
	}

	/**
	 * @param string $appName
	 * @param string $key
	 * @param array $value
	 */
	public function setAppValueArray(string $appName, string $key, array $value): void {
		if (strlen($key) > self::APP_KEY_LIMIT) {
			\OC::$server->get(LoggerInterface::class)
						->warning('key is too long: ' . $key . ' - limit is ' . self::APP_KEY_LIMIT);
		}

		\OC::$server->get(AppConfig::class)->setValueArray($appName, $key, $value);
	}


	/**
	 * Looks up an app wide defined value
	 *
	 * @param string $appName the appName that we stored the value under
	 * @param string $key the key of the value, under which it was saved
	 * @param string $default the default value to be returned if the value isn't set
	 *
	 * @return string the saved value
	 */
	public function getAppValue(string $appName, string $key, $default = '') {
		return \OC::$server->get(AppConfig::class)->getValue($appName, $key, $default);
	}


	public function getAppValueInt(string $appName, string $key, ?int $default = null): int {
		$default = (int)($default ?? $this->appDefaultValues[$appName][$key] ?? 0);

		return \OC::$server->get(AppConfig::class)->getValueInt($appName, $key, $default);
	}

	public function getAppValueBool(string $appName, string $key, ?bool $default = null): bool {
		$default = (bool)($default ?? $this->appDefaultValues[$appName][$key] ?? false);

		return \OC::$server->get(AppConfig::class)->getValueBool($appName, $key, $default);
	}

	public function getAppValueArray(string $appName, string $key, ?array $default = null): array {
		$default = $default ?? $this->appDefaultValues[$appName][$key] ?? [];
		$default = (is_array($default)) ? $default : [];

		return \OC::$server->get(AppConfig::class)->getValueArray($appName, $key, $default);
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
	 * @param string $preCondition only update if the config value was previously the value passed as
	 *     $preCondition
	 *
	 * @throws \OCP\PreConditionNotMetException if a precondition is specified and is not met
	 * @throws \UnexpectedValueException when trying to store an unexpected value
	 */
	public function setUserValue(string $userId, string $appName, string $key, $value, $preCondition = null) {
		if (!is_int($value) && !is_float($value) && !is_string($value)) {
			throw new \UnexpectedValueException('Only integers, floats and strings are allowed as value');
		}

		if (!is_string($value)) {
			\OC::$server->get(LoggerInterface::class)
						->warning(
							'Value used in setUserValue() with config key ' . $key
							. ' is not a string. Please use the suitable method.'
						);
		}

		// TODO - FIXME
		$this->fixDIInit();

		// can it be moved ?
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


	/**
	 * @throws PreConditionNotMetException
	 */
	public function setUserValueInt(
		string $userId,
		string $appName,
		string $key,
		int $value,
		?int $preCondition = null
	) {
		if (strlen($key) > self::USER_KEY_LIMIT) {
			\OC::$server->get(LoggerInterface::class)
						->warning('key ' . $key . ' is too long; limit is ' . self::USER_KEY_LIMIT);
		}

		// TODO - FIXME
		$this->fixDIInit();

		$prevValue = $this->getUserValueInt($userId, $appName, $key);
		if (!is_null($preCondition) && $prevValue !== $preCondition) {
			throw new PreConditionNotMetException();
		}

		if ($prevValue === $value) {
			return;
		}

		$this->prepareInsertOrUpdatePreference($userId, $appName, $key)
			 ->setParameter('configValue', $value, IQueryBuilder::PARAM_INT)
			 ->executeStatement();

		if ($this->isUserCacheInitiated($userId, $appName)) {
			$this->userCache[$userId][$appName][$key] = $value;
		}
	}


	public function setUserValueBool(
		string $userId,
		string $appName,
		string $key,
		bool $value
	) {
		if (strlen($key) > self::USER_KEY_LIMIT) {
			\OC::$server->get(LoggerInterface::class)
						->warning('key ' . $key . ' is too long; limit is ' . self::USER_KEY_LIMIT);
		}

		// TODO - FIXME
		$this->fixDIInit();

		$prevValue = $this->getUserValueBool($userId, $appName, $key);
		if ($prevValue === $value) {
			return;
		}

		$this->prepareInsertOrUpdatePreference($userId, $appName, $key)
			 ->setParameter('configValue', $value, IQueryBuilder::PARAM_BOOL)
			 ->executeStatement();

		if ($this->isUserCacheInitiated($userId, $appName)) {
			$this->userCache[$userId][$appName][$key] = $value;
		}
	}

	public function setUserValueArray(string $userId, string $appName, string $key, array $value) {
		if (strlen($key) > self::USER_KEY_LIMIT) {
			\OC::$server->get(LoggerInterface::class)
						->warning('key ' . $key . ' is too long; limit is ' . self::USER_KEY_LIMIT);
		}

		// TODO - FIXME
		$this->fixDIInit();

		$prevValue = $this->getUserValueArray($userId, $appName, $key);
		if ($prevValue === $value) {
			return;
		}

		$this->prepareInsertOrUpdatePreference($userId, $appName, $key)
			 ->setParameter('configValue', json_encode($value), IQueryBuilder::PARAM_STR)
			 ->executeStatement();

		if ($this->isUserCacheInitiated($userId, $appName)) {
			$this->userCache[$userId][$appName][$key] = $value;
		}
	}


	/**
	 * Getting a user defined value
	 *
	 * @param ?string $userId the userId of the user that we want to store the value under
	 * @param string $appName the appName that we stored the value under
	 * @param string $key the key under which the value is being stored
	 * @param mixed $default the default value to be returned if the value isn't set
	 *
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


	public function getUserValueInt(
		string $userId,
		string $appName,
		string $key,
		?int $default = null
	): int {
		$data = $this->getAllUserValues($userId);

		return (int)($data[$appName][$key] ?? $default ?? $this->userDefaultValues[$key] ?? 0);
	}

	public function getUserValueBool(
		string $userId,
		string $appName,
		string $key,
		?bool $default = null
	): bool {
		$data = $this->getAllUserValues($userId);

		$value = $data[$appName][$key] ?? $default ?? $this->userDefaultValues[$key] ?? false;
		if (is_bool($value)) {
			return $value;
		}

		if (is_string($value) && in_array(strtolower($value), ['0', '1', 'true', 'false'])) {
			return ($value === '1' || strtolower($value) === 'true');
		}

		if (is_numeric($value) && in_array($value, [0, 1])) {
			return ($value === 1);
		}

		return false;
	}

	public function getUserValueArray(
		string $userId,
		string $appName,
		string $key,
		?array $default = null
	): array {
		$data = $this->getAllUserValues($userId);

		$value = $data[$appName][$key] ?? $default ?? $this->userDefaultValues[$key] ?? [];
		if (is_string($value)) {
			$value = json_decode($value, true);
		}

		return (is_array($value)) ? $value : [];
	}


	/**
	 * Get the keys of all stored by an app for the user
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $appName the appName that we stored the value under
	 *
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
		   ->where($qb->expr()->eq('appid', $qb->createNamedParameter($appName, IQueryBuilder::PARAM_STR)))
		   ->where($qb->expr()->eq('configkey', $qb->createNamedParameter($key, IQueryBuilder::PARAM_STR)))
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
	 *
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
					 ->where(
						 $qb->expr()->eq(
							 'userid', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)
						 )
					 )
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
	 *
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
	 *
	 * @return array of user IDs
	 */
	public function getUsersForUserValue($appName, $key, $value) {
		// TODO - FIXME
		$this->fixDIInit();

		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select('userid')
					 ->from('preferences')
					 ->where(
						 $qb->expr()->eq(
							 'appid', $qb->createNamedParameter($appName, IQueryBuilder::PARAM_STR)
						 )
					 )
					 ->andWhere(
						 $qb->expr()->eq(
							 'configkey', $qb->createNamedParameter($key, IQueryBuilder::PARAM_STR)
						 )
					 )
					 ->andWhere(
						 $qb->expr()->eq(
							 $qb->expr()->castColumn('configvalue', IQueryBuilder::PARAM_STR),
							 $qb->createNamedParameter($value, IQueryBuilder::PARAM_STR)
						 )
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
	 *
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
					 ->where(
						 $qb->expr()->eq(
							 'appid', $qb->createNamedParameter($appName, IQueryBuilder::PARAM_STR)
						 )
					 )
					 ->andWhere(
						 $qb->expr()->eq(
							 'configkey', $qb->createNamedParameter($key, IQueryBuilder::PARAM_STR)
						 )
					 )
					 ->andWhere(
						 $qb->expr()->eq(
							 $qb->func()->lower(
								 $qb->expr()->castColumn('configvalue', IQueryBuilder::PARAM_STR)
							 ),
							 $qb->createNamedParameter(strtolower($value), IQueryBuilder::PARAM_STR)
						 )
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


	/**
	 * returns if config key is already known
	 */
	private function hasUserValue(string $userId, string $appName, string $key): bool {
		$data = $this->getAllUserValues($userId);

		return !is_null($data[$appName][$key]);
	}


	/**
	 * Prepare the IQueryBuilder based on user value is already known in database or creation is needed
	 *
	 * The IQueryBuilder only prepare the request and will require the parameter 'configValue' to be set:
	 *
	 *       $this->prepareInsertOrUpdatePreference($userId, $appName, $key)
	 *            ->setParameter('configValue', $value)
	 *            ->executeStatement();
	 *
	 *
	 * @param string $userId
	 * @param string $appName
	 * @param string $key
	 *
	 * @return IQueryBuilder
	 */
	private function prepareInsertOrUpdatePreference(
		string $userId,
		string $appName,
		string $key
	): IQueryBuilder {
		$qb = $this->connection->getQueryBuilder();

		if (!$this->hasUserValue($userId, $appName, $key)) {
			$qb->insert('preferences')
			   ->setValue('configvalue', $qb->createParameter('configValue'))
			   ->setValue('userid', $qb->createNamedParameter($userId))
			   ->setValue('appid', $qb->createNamedParameter($appName))
			   ->setValue('configkey', $qb->createNamedParameter($key));
		} else {
			$qb->update('preferences')
			   ->set('configvalue', $qb->createParameter('configValue'))
			   ->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId)))
			   ->andWhere($qb->expr()->eq('appid', $qb->createNamedParameter($appName)))
			   ->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key)));
		}

		return $qb;
	}


	/**
	 * Returns true if userCache is initiated for $userId
	 * if $appName is provided, will initiate the array if it does not exist yet
	 *
	 * @param string $userId
	 * @param string $appName
	 *
	 * @return bool
	 */
	private function isUserCacheInitiated(string $userId, string $appName = ''): bool {
		if (!isset($this->userCache[$userId])) {
			return false;
		}

		if ($appName !== '' && !isset($this->userCache[$userId][$appName])) {
			$this->userCache[$userId][$appName] = [];
		}

		return true;
	}
}
