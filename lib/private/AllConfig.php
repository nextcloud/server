<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use NCU\Config\Exceptions\TypeConflictException;
use NCU\Config\IUserConfig;
use NCU\Config\ValueType;
use OC\Config\UserConfig;
use OCP\Cache\CappedMemoryCache;
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
		private SystemConfig $systemConfig,
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
	 * @deprecated 29.0.0 Use {@see IAppConfig} directly
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
	 * @deprecated 29.0.0 Use {@see IAppConfig} directly
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
	 * @deprecated 29.0.0 Use {@see IAppConfig} directly
	 */
	public function getAppValue($appName, $key, $default = '') {
		return \OC::$server->get(AppConfig::class)->getValue($appName, $key, $default);
	}

	/**
	 * Delete an app wide defined value
	 *
	 * @param string $appName the appName that we stored the value under
	 * @param string $key the key of the value, under which it was saved
	 * @deprecated 29.0.0 Use {@see IAppConfig} directly
	 */
	public function deleteAppValue($appName, $key) {
		\OC::$server->get(AppConfig::class)->deleteKey($appName, $key);
	}

	/**
	 * Removes all keys in appconfig belonging to the app
	 *
	 * @param string $appName the appName the configs are stored under
	 * @deprecated 29.0.0 Use {@see IAppConfig} directly
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
	 *
	 * @throws \OCP\PreConditionNotMetException if a precondition is specified and is not met
	 * @throws \UnexpectedValueException when trying to store an unexpected value
	 * @deprecated 31.0.0 - use {@see IUserConfig} directly
	 * @see IUserConfig::getValueString
	 * @see IUserConfig::getValueInt
	 * @see IUserConfig::getValueFloat
	 * @see IUserConfig::getValueArray
	 * @see IUserConfig::getValueBool
	 */
	public function setUserValue($userId, $appName, $key, $value, $preCondition = null) {
		if (!is_int($value) && !is_float($value) && !is_string($value)) {
			throw new \UnexpectedValueException('Only integers, floats and strings are allowed as value');
		}

		/** @var UserConfig $userPreferences */
		$userPreferences = \OCP\Server::get(IUserConfig::class);
		if ($preCondition !== null) {
			try {
				if ($userPreferences->hasKey($userId, $appName, $key) && $userPreferences->getValueMixed($userId, $appName, $key) !== (string)$preCondition) {
					throw new PreConditionNotMetException();
				}
			} catch (TypeConflictException) {
			}
		}

		$userPreferences->setValueMixed($userId, $appName, $key, (string)$value);
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
	 * @deprecated 31.0.0 - use {@see IUserConfig} directly
	 * @see IUserConfig::getValueString
	 * @see IUserConfig::getValueInt
	 * @see IUserConfig::getValueFloat
	 * @see IUserConfig::getValueArray
	 * @see IUserConfig::getValueBool
	 */
	public function getUserValue($userId, $appName, $key, $default = '') {
		if ($userId === null || $userId === '') {
			return $default;
		}
		/** @var UserConfig $userPreferences */
		$userPreferences = \OCP\Server::get(IUserConfig::class);
		// because $default can be null ...
		if (!$userPreferences->hasKey($userId, $appName, $key)) {
			return $default;
		}
		return $userPreferences->getValueMixed($userId, $appName, $key, $default ?? '');
	}

	/**
	 * Get the keys of all stored by an app for the user
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $appName the appName that we stored the value under
	 *
	 * @return string[]
	 * @deprecated 31.0.0 - use {@see IUserConfig::getKeys} directly
	 */
	public function getUserKeys($userId, $appName) {
		return \OCP\Server::get(IUserConfig::class)->getKeys($userId, $appName);
	}

	/**
	 * Delete a user value
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $appName the appName that we stored the value under
	 * @param string $key the key under which the value is being stored
	 *
	 * @deprecated 31.0.0 - use {@see IUserConfig::deleteUserConfig} directly
	 */
	public function deleteUserValue($userId, $appName, $key) {
		\OCP\Server::get(IUserConfig::class)->deleteUserConfig($userId, $appName, $key);
	}

	/**
	 * Delete all user values
	 *
	 * @param string $userId the userId of the user that we want to remove all values from
	 *
	 * @deprecated 31.0.0 - use {@see IUserConfig::deleteAllUserConfig} directly
	 */
	public function deleteAllUserValues($userId) {
		if ($userId === null) {
			return;
		}
		\OCP\Server::get(IUserConfig::class)->deleteAllUserConfig($userId);
	}

	/**
	 * Delete all user related values of one app
	 *
	 * @param string $appName the appName of the app that we want to remove all values from
	 *
	 * @deprecated 31.0.0 - use {@see IUserConfig::deleteApp} directly
	 */
	public function deleteAppFromAllUsers($appName) {
		\OCP\Server::get(IUserConfig::class)->deleteApp($appName);
	}

	/**
	 * Returns all user configs sorted by app of one user
	 *
	 * @param ?string $userId the user ID to get the app configs from
	 *
	 * @psalm-return array<string, array<string, string>>
	 * @return array[] - 2 dimensional array with the following structure:
	 *                 [ $appId =>
	 *                 [ $key => $value ]
	 *                 ]
	 * @deprecated 31.0.0 - use {@see IUserConfig::getAllValues} directly
	 */
	public function getAllUserValues(?string $userId): array {
		if ($userId === null || $userId === '') {
			return [];
		}

		$values = \OCP\Server::get(IUserConfig::class)->getAllValues($userId);
		$result = [];
		foreach ($values as $app => $list) {
			foreach ($list as $key => $value) {
				$result[$app][$key] = (string)$value;
			}
		}
		return $result;
	}

	/**
	 * Fetches a mapped list of userId -> value, for a specified app and key and a list of user IDs.
	 *
	 * @param string $appName app to get the value for
	 * @param string $key the key to get the value for
	 * @param array $userIds the user IDs to fetch the values for
	 *
	 * @return array Mapped values: userId => value
	 * @deprecated 31.0.0 - use {@see IUserConfig::getValuesByUsers} directly
	 */
	public function getUserValueForUsers($appName, $key, $userIds) {
		return \OCP\Server::get(IUserConfig::class)->getValuesByUsers($appName, $key, ValueType::MIXED, $userIds);
	}

	/**
	 * Determines the users that have the given value set for a specific app-key-pair
	 *
	 * @param string $appName the app to get the user for
	 * @param string $key the key to get the user for
	 * @param string $value the value to get the user for
	 *
	 * @return list<string> of user IDs
	 * @deprecated 31.0.0 - use {@see IUserConfig::searchUsersByValueString} directly
	 */
	public function getUsersForUserValue($appName, $key, $value) {
		/** @var list<string> $result */
		$result = iterator_to_array(\OCP\Server::get(IUserConfig::class)->searchUsersByValueString($appName, $key, $value));
		return $result;
	}

	/**
	 * Determines the users that have the given value set for a specific app-key-pair
	 *
	 * @param string $appName the app to get the user for
	 * @param string $key the key to get the user for
	 * @param string $value the value to get the user for
	 *
	 * @return list<string> of user IDs
	 * @deprecated 31.0.0 - use {@see IUserConfig::searchUsersByValueString} directly
	 */
	public function getUsersForUserValueCaseInsensitive($appName, $key, $value) {
		if ($appName === 'settings' && $key === 'email') {
			return $this->getUsersForUserValue($appName, $key, strtolower($value));
		}

		/** @var list<string> $result */
		$result = iterator_to_array(\OCP\Server::get(IUserConfig::class)->searchUsersByValueString($appName, $key, $value, true));
		return $result;
	}

	public function getSystemConfig() {
		return $this->systemConfig;
	}
}
