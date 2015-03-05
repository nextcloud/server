<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */

namespace OC;
use OCP\IDBConnection;
use OCP\PreConditionNotMetException;

/**
 * Class to combine all the configuration options ownCloud offers
 */
class AllConfig implements \OCP\IConfig {
	/** @var SystemConfig */
	private $systemConfig;

	/** @var IDBConnection */
	private $connection;

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
	 * @var array $userCache
	 */
	private $userCache = array();

	/**
	 * @param SystemConfig $systemConfig
	 */
	function __construct(SystemConfig $systemConfig) {
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
		if($this->connection === null) {
			$this->connection = \OC::$server->getDatabaseConnection();
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
		return \OC::$server->getAppConfig()->getKeys($appName);
	}

	/**
	 * Writes a new app wide value
	 *
	 * @param string $appName the appName that we want to store the value under
	 * @param string $key the key of the value, under which will be saved
	 * @param string $value the value that should be stored
	 */
	public function setAppValue($appName, $key, $value) {
		\OC::$server->getAppConfig()->setValue($appName, $key, $value);
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
		return \OC::$server->getAppConfig()->getValue($appName, $key, $default);
	}

	/**
	 * Delete an app wide defined value
	 *
	 * @param string $appName the appName that we stored the value under
	 * @param string $key the key of the value, under which it was saved
	 */
	public function deleteAppValue($appName, $key) {
		\OC::$server->getAppConfig()->deleteKey($appName, $key);
	}

	/**
	 * Removes all keys in appconfig belonging to the app
	 *
	 * @param string $appName the appName the configs are stored under
	 */
	public function deleteAppValues($appName) {
		\OC::$server->getAppConfig()->deleteApp($appName);
	}

	/**
	 * Set a user defined value
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $appName the appName that we want to store the value under
	 * @param string $key the key under which the value is being stored
	 * @param string $value the value that you want to store
	 * @param string $preCondition only update if the config value was previously the value passed as $preCondition
	 * @throws \OCP\PreConditionNotMetException if a precondition is specified and is not met
	 */
	public function setUserValue($userId, $appName, $key, $value, $preCondition = null) {
		// TODO - FIXME
		$this->fixDIInit();

		// Check if the key does exist
		$sql  = 'SELECT `configvalue` FROM `*PREFIX*preferences` '.
				'WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?';
		$result = $this->connection->executeQuery($sql, array($userId, $appName, $key));
		$oldValue = $result->fetchColumn();
		$result->closeCursor();
		$exists = $oldValue !== false;

		if($oldValue === strval($value)) {
			// no changes
			return;
		}

		$data = array($value, $userId, $appName, $key);
		if (!$exists && $preCondition === null) {
			$sql  = 'INSERT INTO `*PREFIX*preferences` (`configvalue`, `userid`, `appid`, `configkey`)'.
					'VALUES (?, ?, ?, ?)';
		} elseif ($exists) {
			$sql  = 'UPDATE `*PREFIX*preferences` SET `configvalue` = ? '.
					'WHERE `userid` = ? AND `appid` = ? AND `configkey` = ? ';

			if($preCondition !== null) {
				if($this->getSystemValue('dbtype', 'sqlite') === 'oci') {
					//oracle hack: need to explicitly cast CLOB to CHAR for comparison
					$sql .= 'AND to_char(`configvalue`) = ?';
				} else {
					$sql .= 'AND `configvalue` = ?';
				}
				$data[] = $preCondition;
			}
		}
		$affectedRows = $this->connection->executeUpdate($sql, $data);

		// only add to the cache if we already loaded data for the user
		if ($affectedRows > 0 && isset($this->userCache[$userId])) {
			if (!isset($this->userCache[$userId][$appName])) {
				$this->userCache[$userId][$appName] = array();
			}
			$this->userCache[$userId][$appName][$key] = $value;
		}

		if ($preCondition !== null && $affectedRows === 0) {
			throw new PreConditionNotMetException;
		}
	}

	/**
	 * Getting a user defined value
	 *
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $appName the appName that we stored the value under
	 * @param string $key the key under which the value is being stored
	 * @param string $default the default value to be returned if the value isn't set
	 * @return string
	 */
	public function getUserValue($userId, $appName, $key, $default = '') {
		$data = $this->getUserValues($userId);
		if (isset($data[$appName]) and isset($data[$appName][$key])) {
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
		$data = $this->getUserValues($userId);
		if (isset($data[$appName])) {
			return array_keys($data[$appName]);
		} else {
			return array();
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

		$sql  = 'DELETE FROM `*PREFIX*preferences` '.
				'WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?';
		$this->connection->executeUpdate($sql, array($userId, $appName, $key));

		if (isset($this->userCache[$userId]) and isset($this->userCache[$userId][$appName])) {
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

		$sql  = 'DELETE FROM `*PREFIX*preferences` '.
			'WHERE `userid` = ?';
		$this->connection->executeUpdate($sql, array($userId));

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

		$sql  = 'DELETE FROM `*PREFIX*preferences` '.
				'WHERE `appid` = ?';
		$this->connection->executeUpdate($sql, array($appName));

		foreach ($this->userCache as &$userCache) {
			unset($userCache[$appName]);
		}
	}

	/**
	 * Returns all user configs sorted by app of one user
	 *
	 * @param string $userId the user ID to get the app configs from
	 * @return array[] - 2 dimensional array with the following structure:
	 *     [ $appId =>
	 *         [ $key => $value ]
	 *     ]
	 */
	private function getUserValues($userId) {
		// TODO - FIXME
		$this->fixDIInit();

		if (isset($this->userCache[$userId])) {
			return $this->userCache[$userId];
		}
		$data = array();
		$query = 'SELECT `appid`, `configkey`, `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ?';
		$result = $this->connection->executeQuery($query, array($userId));
		while ($row = $result->fetch()) {
			$appId = $row['appid'];
			if (!isset($data[$appId])) {
				$data[$appId] = array();
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
			return array();
		}

		$chunkedUsers = array_chunk($userIds, 50, true);
		$placeholders50 = implode(',', array_fill(0, 50, '?'));

		$userValues = array();
		foreach ($chunkedUsers as $chunk) {
			$queryParams = $chunk;
			// create [$app, $key, $chunkedUsers]
			array_unshift($queryParams, $key);
			array_unshift($queryParams, $appName);

			$placeholders = (sizeof($chunk) == 50) ? $placeholders50 :  implode(',', array_fill(0, sizeof($chunk), '?'));

			$query    = 'SELECT `userid`, `configvalue` ' .
						'FROM `*PREFIX*preferences` ' .
						'WHERE `appid` = ? AND `configkey` = ? ' .
						'AND `userid` IN (' . $placeholders . ')';
			$result = $this->connection->executeQuery($query, $queryParams);

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

		$sql  = 'SELECT `userid` FROM `*PREFIX*preferences` ' .
				'WHERE `appid` = ? AND `configkey` = ? ';

		if($this->getSystemValue('dbtype', 'sqlite') === 'oci') {
			//oracle hack: need to explicitly cast CLOB to CHAR for comparison
			$sql .= 'AND to_char(`configvalue`) = ?';
		} else {
			$sql .= 'AND `configvalue` = ?';
		}

		$result = $this->connection->executeQuery($sql, array($appName, $key, $value));

		$userIDs = array();
		while ($row = $result->fetch()) {
			$userIDs[] = $row['userid'];
		}

		return $userIDs;
	}
}
