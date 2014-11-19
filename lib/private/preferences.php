<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
/*
 *
 * The following SQL statement is just a help for developers and will not be
 * executed!
 *
 * CREATE TABLE  `preferences` (
 * `userid` VARCHAR( 255 ) NOT NULL ,
 * `appid` VARCHAR( 255 ) NOT NULL ,
 * `configkey` VARCHAR( 255 ) NOT NULL ,
 * `configvalue` VARCHAR( 255 ) NOT NULL
 * )
 *
 */

namespace OC;

use OCP\IDBConnection;


/**
 * This class provides an easy way for storing user preferences.
 */
class Preferences {
	/**
	 * @var \OC\DB\Connection
	 */
	protected $conn;

	/**
	 * 3 dimensional array with the following structure:
	 * [ $userId =>
	 *     [ $appId =>
	 *         [ $key => $value ]
	 *     ]
	 * ]
	 *
	 * @var array $cache
	 */
	protected $cache = array();

	/**
	 * @param \OCP\IDBConnection $conn
	 */
	public function __construct(IDBConnection $conn) {
		$this->conn = $conn;
	}

	/**
	 * @param string $user
	 * @return array[]
	 */
	protected function getUserValues($user) {
		if (isset($this->cache[$user])) {
			return $this->cache[$user];
		}
		$data = array();
		$query = 'SELECT `appid`, `configkey`, `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ?';
		$result = $this->conn->executeQuery($query, array($user));
		while ($row = $result->fetch()) {
			$app = $row['appid'];
			if (!isset($data[$app])) {
				$data[$app] = array();
			}
			$data[$app][$row['configkey']] = $row['configvalue'];
		}
		$this->cache[$user] = $data;
		return $data;
	}

	/**
	 * Get the available keys for an app
	 * @param string $user user
	 * @param string $app the app we are looking for
	 * @return array an array of key names
	 *
	 * This function gets all keys of an app of an user. Please note that the
	 * values are not returned.
	 */
	public function getKeys($user, $app) {
		$data = $this->getUserValues($user);
		if (isset($data[$app])) {
			return array_keys($data[$app]);
		} else {
			return array();
		}
	}

	/**
	 * Gets the preference
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @param string $default = null, default value if the key does not exist
	 * @return string the value or $default
	 *
	 * This function gets a value from the preferences table. If the key does
	 * not exist the default value will be returned
	 */
	public function getValue($user, $app, $key, $default = null) {
		$data = $this->getUserValues($user);
		if (isset($data[$app]) and isset($data[$app][$key])) {
			return $data[$app][$key];
		} else {
			return $default;
		}
	}

	/**
	 * sets a value in the preferences
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @param string $value value
	 * @param string $preCondition only set value if the key had a specific value before
	 * @return bool true if value was set, otherwise false
	 *
	 * Adds a value to the preferences. If the key did not exist before, it
	 * will be added automagically.
	 */
	public function setValue($user, $app, $key, $value, $preCondition = null) {
		// Check if the key does exist
		$query = 'SELECT `configvalue` FROM `*PREFIX*preferences`'
			. ' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?';
		$oldValue = $this->conn->fetchColumn($query, array($user, $app, $key));
		$exists = $oldValue !== false;

		if($oldValue === strval($value)) {
			// no changes
			return true;
		}
		
		$affectedRows = 0;

		if (!$exists && $preCondition === null) {
			$data = array(
				'userid' => $user,
				'appid' => $app,
				'configkey' => $key,
				'configvalue' => $value,
			);
			$affectedRows = $this->conn->insert('*PREFIX*preferences', $data);
		} elseif ($exists) {
			$data = array($value, $user, $app, $key);
			$sql  = "UPDATE `*PREFIX*preferences` SET `configvalue` = ?"
					. " WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?";

			if ($preCondition !== null) {
				if (\OC_Config::getValue( 'dbtype', 'sqlite' ) === 'oci') {
					//oracle hack: need to explicitly cast CLOB to CHAR for comparison
					$sql .= " AND to_char(`configvalue`) = ?";
				} else {
					$sql .= " AND `configvalue` = ?";
				}
				$data[] = $preCondition;
			}
			$affectedRows = $this->conn->executeUpdate($sql, $data);
		}

		// only add to the cache if we already loaded data for the user
		if ($affectedRows > 0 && isset($this->cache[$user])) {
			if (!isset($this->cache[$user][$app])) {
				$this->cache[$user][$app] = array();
			}
			$this->cache[$user][$app][$key] = $value;
		}

		return ($affectedRows > 0) ? true : false;

	}

	/**
	 * Gets the preference for an array of users
	 * @param string $app
	 * @param string $key
	 * @param array $users
	 * @return array Mapped values: userid => value
	 */
	public function getValueForUsers($app, $key, $users) {
		if (empty($users) || !is_array($users)) {
			return array();
		}

		$chunked_users = array_chunk($users, 50, true);
		$placeholders_50 = implode(',', array_fill(0, 50, '?'));

		$userValues = array();
		foreach ($chunked_users as $chunk) {
			$queryParams = $chunk;
			array_unshift($queryParams, $key);
			array_unshift($queryParams, $app);

			$placeholders = (sizeof($chunk) == 50) ? $placeholders_50 : implode(',', array_fill(0, sizeof($chunk), '?'));

			$query = 'SELECT `userid`, `configvalue` '
				. ' FROM `*PREFIX*preferences` '
				. ' WHERE `appid` = ? AND `configkey` = ?'
				. ' AND `userid` IN (' . $placeholders . ')';
			$result = $this->conn->executeQuery($query, $queryParams);

			while ($row = $result->fetch()) {
				$userValues[$row['userid']] = $row['configvalue'];
			}
		}

		return $userValues;
	}

	/**
	 * Gets the users for a preference
	 * @param string $app
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public function getUsersForValue($app, $key, $value) {
		$users = array();

		$query = 'SELECT `userid` '
			. ' FROM `*PREFIX*preferences` '
			. ' WHERE `appid` = ? AND `configkey` = ? AND ';

		if (\OC_Config::getValue( 'dbtype', 'sqlite' ) === 'oci') {
			//FIXME oracle hack: need to explicitly cast CLOB to CHAR for comparison
			$query .= ' to_char(`configvalue`)= ?';
		} else {
			$query .= ' `configvalue` = ?';
		}

		$result = $this->conn->executeQuery($query, array($app, $key, $value));

		while ($row = $result->fetch()) {
			$users[] = $row['userid'];
		}

		return $users;
	}

	/**
	 * Deletes a key
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 *
	 * Deletes a key.
	 */
	public function deleteKey($user, $app, $key) {
		$where = array(
			'userid' => $user,
			'appid' => $app,
			'configkey' => $key,
		);
		$this->conn->delete('*PREFIX*preferences', $where);

		if (isset($this->cache[$user]) and isset($this->cache[$user][$app])) {
			unset($this->cache[$user][$app][$key]);
		}
	}

	/**
	 * Remove app of user from preferences
	 * @param string $user user
	 * @param string $app app
	 *
	 * Removes all keys in preferences belonging to the app and the user.
	 */
	public function deleteApp($user, $app) {
		$where = array(
			'userid' => $user,
			'appid' => $app,
		);
		$this->conn->delete('*PREFIX*preferences', $where);

		if (isset($this->cache[$user])) {
			unset($this->cache[$user][$app]);
		}
	}

	/**
	 * Remove user from preferences
	 * @param string $user user
	 *
	 * Removes all keys in preferences belonging to the user.
	 */
	public function deleteUser($user) {
		$where = array(
			'userid' => $user,
		);
		$this->conn->delete('*PREFIX*preferences', $where);

		unset($this->cache[$user]);
	}

	/**
	 * Remove app from all users
	 * @param string $app app
	 *
	 * Removes all keys in preferences belonging to the app.
	 */
	public function deleteAppFromAllUsers($app) {
		$where = array(
			'appid' => $app,
		);
		$this->conn->delete('*PREFIX*preferences', $where);

		foreach ($this->cache as &$userCache) {
			unset($userCache[$app]);
		}
	}
}

require_once __DIR__ . '/legacy/' . basename(__FILE__);
