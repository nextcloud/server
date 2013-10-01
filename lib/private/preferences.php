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

use \OC\DB\Connection;


/**
 * This class provides an easy way for storing user preferences.
 */
class Preferences {
	protected $conn;

	public function __construct(Connection $conn) {
		$this->conn = $conn;
	}

	/**
	 * @brief Get all users using the preferences
	 * @return array with user ids
	 *
	 * This function returns a list of all users that have at least one entry
	 * in the preferences table.
	 */
	public function getUsers() {
		$query = 'SELECT DISTINCT `userid` FROM `*PREFIX*preferences`';
		$result = $this->conn->executeQuery( $query );

		$users = array();
		while( $userid = $result->fetchColumn()) {
			$users[] = $userid;
		}

		return $users;
	}

	/**
	 * @brief Get all apps of an user
	 * @param string $user user
	 * @return array with app ids
	 *
	 * This function returns a list of all apps of the user that have at least
	 * one entry in the preferences table.
	 */
	public function getApps( $user ) {
		$query = 'SELECT DISTINCT `appid` FROM `*PREFIX*preferences` WHERE `userid` = ?';
		$result = $this->conn->executeQuery( $query, array( $user ) );

		$apps = array();
		while( $appid = $result->fetchColumn()) {
			$apps[] = $appid;
		}

		return $apps;
	}

	/**
	 * @brief Get the available keys for an app
	 * @param string $user user
	 * @param string $app the app we are looking for
	 * @return array with key names
	 *
	 * This function gets all keys of an app of an user. Please note that the
	 * values are not returned.
	 */
	public function getKeys( $user, $app ) {
		$query = 'SELECT `configkey` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ?';
		$result = $this->conn->executeQuery( $query, array( $user, $app ));

		$keys = array();
		while( $key = $result->fetchColumn()) {
			$keys[] = $key;
		}

		return $keys;
	}

	/**
	 * @brief Gets the preference
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @param string $default = null, default value if the key does not exist
	 * @return string the value or $default
	 *
	 * This function gets a value from the preferences table. If the key does
	 * not exist the default value will be returned
	 */
	public function getValue( $user, $app, $key, $default = null ) {
		// Try to fetch the value, return default if not exists.
		$query = 'SELECT `configvalue` FROM `*PREFIX*preferences`'
			.' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?';
		$row = $this->conn->fetchAssoc( $query, array( $user, $app, $key ));
		if($row) {
			return $row["configvalue"];
		} else {
			return $default;
		}
	}

	/**
	 * @brief sets a value in the preferences
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @param string $value value
	 *
	 * Adds a value to the preferences. If the key did not exist before, it
	 * will be added automagically.
	 */
	public function setValue( $user, $app, $key, $value ) {
		// Check if the key does exist
		$query = 'SELECT COUNT(*) FROM `*PREFIX*preferences`'
			.' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?';
		$count = $this->conn->fetchColumn( $query, array( $user, $app, $key ));
		$exists = $count > 0;

		if( !$exists ) {
			$data = array(
				'userid' => $user,
				'appid' => $app,
				'configkey' => $key,
				'configvalue' => $value,
			);
			$this->conn->insert('*PREFIX*preferences', $data);
		} else {
			$data = array(
				'configvalue' => $value,
			);
			$where = array(
				'userid' => $user,
				'appid' => $app,
				'configkey' => $key,
			);
			$this->conn->update('*PREFIX*preferences', $data, $where);
		}
	}

	/**
	 * @brief Deletes a key
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 *
	 * Deletes a key.
	 */
	public function deleteKey( $user, $app, $key ) {
		$where = array(
			'userid' => $user,
			'appid' => $app,
			'configkey' => $key,
		);
		$this->conn->delete('*PREFIX*preferences', $where);
	}

	/**
	 * @brief Remove app of user from preferences
	 * @param string $user user
	 * @param string $app app
	 *
	 * Removes all keys in preferences belonging to the app and the user.
	 */
	public function deleteApp( $user, $app ) {
		$where = array(
			'userid' => $user,
			'appid' => $app,
		);
		$this->conn->delete('*PREFIX*preferences', $where);
	}

	/**
	 * @brief Remove user from preferences
	 * @param string $user user
	 *
	 * Removes all keys in preferences belonging to the user.
	 */
	public function deleteUser( $user ) {
		$where = array(
			'userid' => $user,
		);
		$this->conn->delete('*PREFIX*preferences', $where);
	}

	/**
	 * @brief Remove app from all users
	 * @param string $app app
	 *
	 * Removes all keys in preferences belonging to the app.
	 */
	public function deleteAppFromAllUsers( $app ) {
		$where = array(
			'appid' => $app,
		);
		$this->conn->delete('*PREFIX*preferences', $where);
	}
}

require_once __DIR__.'/legacy/'.basename(__FILE__);
