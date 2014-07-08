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
 * CREATE TABLE  `appconfig` (
 * `appid` VARCHAR( 255 ) NOT NULL ,
 * `configkey` VARCHAR( 255 ) NOT NULL ,
 * `configvalue` VARCHAR( 255 ) NOT NULL
 * )
 *
 */

namespace OC;

use \OC\DB\Connection;

/**
 * This class provides an easy way for apps to store config values in the
 * database.
 */
class AppConfig implements \OCP\IAppConfig {
	/**
	 * @var \OC\DB\Connection $conn
	 */
	protected $conn;

	private $cache = array();

	private $appsLoaded = array();

	/**
	 * @var string[]
	 */
	private $apps = null;

	/**
	 * @param \OC\DB\Connection $conn
	 */
	public function __construct(Connection $conn) {
		$this->conn = $conn;
	}

	/**
	 * @param string $app
	 * @return string[]
	 */
	private function getAppCache($app) {
		if (!isset($this->cache[$app])) {
			$this->cache[$app] = array();
		}
		return $this->cache[$app];
	}

	/**
	 * @param string $app
	 * @return \string[]
	 */
	private function getAppValues($app) {
		$appCache = $this->getAppCache($app);
		if (array_search($app, $this->appsLoaded) === false) {
			$query = 'SELECT `configvalue`, `configkey` FROM `*PREFIX*appconfig`'
				. ' WHERE `appid` = ?';
			$result = $this->conn->executeQuery($query, array($app));
			while ($row = $result->fetch()) {
				$appCache[$row['configkey']] = $row['configvalue'];
			}
			$this->appsLoaded[] = $app;
		}
		$this->cache[$app] = $appCache;
		return $appCache;
	}

	/**
	 * Get all apps using the config
	 *
	 * @return array an array of app ids
	 *
	 * This function returns a list of all apps that have at least one
	 * entry in the appconfig table.
	 */
	public function getApps() {
		if (is_array($this->apps)) {
			return $this->apps;
		}
		$query = 'SELECT DISTINCT `appid` FROM `*PREFIX*appconfig` ORDER BY `appid`';
		$result = $this->conn->executeQuery($query);

		$apps = array();
		while ($appid = $result->fetchColumn()) {
			$apps[] = $appid;
		}
		$this->apps = $apps;
		return $apps;
	}

	/**
	 * Get the available keys for an app
	 *
	 * @param string $app the app we are looking for
	 * @return array an array of key names
	 *
	 * This function gets all keys of an app. Please note that the values are
	 * not returned.
	 */
	public function getKeys($app) {
		$values = $this->getAppValues($app);
		$keys = array_keys($values);
		sort($keys);
		return $keys;
	}

	/**
	 * Gets the config value
	 *
	 * @param string $app app
	 * @param string $key key
	 * @param string $default = null, default value if the key does not exist
	 * @return string the value or $default
	 *
	 * This function gets a value from the appconfig table. If the key does
	 * not exist the default value will be returned
	 */
	public function getValue($app, $key, $default = null) {
		$values = $this->getAppValues($app);
		if (isset($values[$key])) {
			return $values[$key];
		} else {
			return $default;
		}
	}

	/**
	 * check if a key is set in the appconfig
	 *
	 * @param string $app
	 * @param string $key
	 * @return bool
	 */
	public function hasKey($app, $key) {
		$values = $this->getAppValues($app);
		return array_key_exists($key, $values);
	}

	/**
	 * sets a value in the appconfig
	 *
	 * @param string $app app
	 * @param string $key key
	 * @param string $value value
	 *
	 * Sets a value. If the key did not exist before it will be created.
	 */
	public function setValue($app, $key, $value) {
		// Does the key exist? no: insert, yes: update.
		if (!$this->hasKey($app, $key)) {
			$data = array(
				'appid' => $app,
				'configkey' => $key,
				'configvalue' => $value,
			);
			$this->conn->insert('*PREFIX*appconfig', $data);
		} else {
			$oldValue = $this->getValue($app, $key);
			if($oldValue === strval($value)) {
				return true;
			}
			$data = array(
				'configvalue' => $value,
			);
			$where = array(
				'appid' => $app,
				'configkey' => $key,
			);
			$this->conn->update('*PREFIX*appconfig', $data, $where);
		}
		if (!isset($this->cache[$app])) {
			$this->cache[$app] = array();
		}
		if (is_array($this->apps) and array_search($app, $this->apps) === false) {
			$this->apps[$app] = $app;
		}
		$this->cache[$app][$key] = $value;
	}

	/**
	 * Deletes a key
	 *
	 * @param string $app app
	 * @param string $key key
	 * @return boolean|null
	 */
	public function deleteKey($app, $key) {
		$where = array(
			'appid' => $app,
			'configkey' => $key,
		);
		$this->conn->delete('*PREFIX*appconfig', $where);
		if (isset($this->cache[$app]) and isset($this->cache[$app][$key])) {
			unset($this->cache[$app][$key]);
		}
	}

	/**
	 * Remove app from appconfig
	 *
	 * @param string $app app
	 * @return boolean|null
	 *
	 * Removes all keys in appconfig belonging to the app.
	 */
	public function deleteApp($app) {
		$where = array(
			'appid' => $app,
		);
		$this->conn->delete('*PREFIX*appconfig', $where);
		unset($this->cache[$app]);
		unset($this->apps[$app]);
	}

	/**
	 * get multiply values, either the app or key can be used as wildcard by setting it to false
	 *
	 * @param string|false $app
	 * @param string|false $key
	 * @return array
	 */
	public function getValues($app, $key) {
		if (($app !== false) == ($key !== false)) {
			return false;
		}

		if ($app !== false) {
			return $this->getAppValues($app);
		} else {
			$query = 'SELECT `configvalue`, `appid` FROM `*PREFIX*appconfig` WHERE `configkey` = ?';
			$result = $this->conn->executeQuery($query, array($key));

			$values = array();
			while ($row = $result->fetch((\PDO::FETCH_ASSOC))) {
				$values[$row['appid']] = $row['configvalue'];
			}

			return $values;
		}
	}
}
