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
class AppConfig {
	protected $conn;

	public function __construct(Connection $conn) {
		$this->conn = $conn;
	}

	/**
	 * @brief Get all apps using the config
	 * @return array with app ids
	 *
	 * This function returns a list of all apps that have at least one
	 * entry in the appconfig table.
	 */
	public function getApps() {
		$query = 'SELECT DISTINCT `appid` FROM `*PREFIX*appconfig`';
		$result = $this->conn->executeQuery( $query );

		$apps = array();
		while( $appid = $result->fetchColumn()) {
			$apps[] = $appid;
		}
		return $apps;
	}

	/**
	 * @brief Get the available keys for an app
	 * @param string $app the app we are looking for
	 * @return array with key names
	 *
	 * This function gets all keys of an app. Please note that the values are
	 * not returned.
	 */
	public function getKeys( $app ) {
		$query = 'SELECT `configkey` FROM `*PREFIX*appconfig` WHERE `appid` = ?';
		$result = $this->conn->executeQuery( $query, array( $app ));

		$keys = array();
		while( $key = $result->fetchColumn()) {
			$keys[] = $key;
		}

		return $keys;
	}

	/**
	 * @brief Gets the config value
	 * @param string $app app
	 * @param string $key key
	 * @param string $default = null, default value if the key does not exist
	 * @return string the value or $default
	 *
	 * This function gets a value from the appconfig table. If the key does
	 * not exist the default value will be returned
	 */
	public function getValue( $app, $key, $default = null ) {
		$query = 'SELECT `configvalue` FROM `*PREFIX*appconfig`'
			.' WHERE `appid` = ? AND `configkey` = ?';
		$row = $this->conn->fetchAssoc($query, array( $app, $key ));
		if($row) {
			return $row['configvalue'];
		} else {
			return $default;
		}
	}

	/**
	 * @brief check if a key is set in the appconfig
	 * @param string $app
	 * @param string $key
	 * @return bool
	 */
	public function hasKey($app, $key) {
		$exists = $this->getKeys( $app );
		return in_array( $key, $exists );
	}

	/**
	 * @brief sets a value in the appconfig
	 * @param string $app app
	 * @param string $key key
	 * @param string $value value
	 *
	 * Sets a value. If the key did not exist before it will be created.
	 */
	public function setValue( $app, $key, $value ) {
		// Does the key exist? no: insert, yes: update.
		if ( !$this->hasKey($app, $key)) {
			$data = array(
				'appid' => $app,
				'configkey' => $key,
				'configvalue' => $value,
			);
			$this->conn->insert('*PREFIX*appconfig', $data);
		} else {
			$data = array(
				'configvalue' => $value,
			);
			$where = array(
				'appid' => $app,
				'configkey' => $key,
			);
			$this->conn->update('*PREFIX*appconfig', $data, $where);
		}
	}

	/**
	 * @brief Deletes a key
	 * @param string $app app
	 * @param string $key key
	 * @return bool
	 *
	 * Deletes a key.
	 */
	public function deleteKey( $app, $key ) {
		$where = array(
			'appid' => $app,
			'configkey' => $key,
		);
		$this->conn->delete('*PREFIX*appconfig', $where);
	}

	/**
	 * @brief Remove app from appconfig
	 * @param string $app app
	 * @return bool
	 *
	 * Removes all keys in appconfig belonging to the app.
	 */
	public function deleteApp( $app ) {
		$where = array(
			'appid' => $app,
		);
		$this->conn->delete('*PREFIX*appconfig', $where);
	}

	/**
	 * get multiply values, either the app or key can be used as wildcard by setting it to false
	 * @param app
	 * @param key
	 * @return array
	 */
	public function getValues($app, $key) {
		if(($app!==false) == ($key!==false)) {
			return false;
		}

		$fields = '`configvalue`';
		$where = 'WHERE';
		$params = array();
		if($app!==false) {
			$fields .= ', `configkey`';
			$where .= ' `appid` = ?';
			$params[] = $app;
			$key = 'configkey';
		}else{
			$fields .= ', `appid`';
			$where .= ' `configkey` = ?';
			$params[] = $key;
			$key = 'appid';
		}
		$query = 'SELECT '.$fields.' FROM `*PREFIX*appconfig` '.$where;
		$result = $this->conn->executeQuery( $query, $params );

		$values = array();
		while( $row = $result->fetch((\PDO::FETCH_ASSOC))) {
			$values[$row[$key]] = $row['configvalue'];
		}

		return $values;
	}
}

require_once __DIR__.'/legacy/'.basename(__FILE__);
