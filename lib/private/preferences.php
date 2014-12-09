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
use OCP\PreConditionNotMetException;


/**
 * This class provides an easy way for storing user preferences.
 * @deprecated use \OCP\IConfig methods instead
 */
class Preferences {

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

	/** @var \OCP\IConfig */
	protected $config;

	/**
	 * @param \OCP\IDBConnection $conn
	 */
	public function __construct(IDBConnection $conn) {
		$this->config = \OC::$server->getConfig();
	}

	/**
	 * Get the available keys for an app
	 * @param string $user user
	 * @param string $app the app we are looking for
	 * @return array an array of key names
	 * @deprecated use getUserKeys of \OCP\IConfig instead
	 *
	 * This function gets all keys of an app of an user. Please note that the
	 * values are not returned.
	 */
	public function getKeys($user, $app) {
		return $this->config->getUserKeys($user, $app);
	}

	/**
	 * Gets the preference
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @param string $default = null, default value if the key does not exist
	 * @return string the value or $default
	 * @deprecated use getUserValue of \OCP\IConfig instead
	 *
	 * This function gets a value from the preferences table. If the key does
	 * not exist the default value will be returned
	 */
	public function getValue($user, $app, $key, $default = null) {
		return $this->config->getUserValue($user, $app, $key, $default);
	}

	/**
	 * sets a value in the preferences
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @param string $value value
	 * @param string $preCondition only set value if the key had a specific value before
	 * @return bool true if value was set, otherwise false
	 * @deprecated use setUserValue of \OCP\IConfig instead
	 *
	 * Adds a value to the preferences. If the key did not exist before, it
	 * will be added automagically.
	 */
	public function setValue($user, $app, $key, $value, $preCondition = null) {
		try {
			$this->config->setUserValue($user, $app, $key, $value, $preCondition);
			return true;
		} catch(PreConditionNotMetException $e) {
			return false;
		}
	}

	/**
	 * Gets the preference for an array of users
	 * @param string $app
	 * @param string $key
	 * @param array $users
	 * @return array Mapped values: userid => value
	 * @deprecated use getUserValueForUsers of \OCP\IConfig instead
	 */
	public function getValueForUsers($app, $key, $users) {
		return $this->config->getUserValueForUsers($app, $key, $users);
	}

	/**
	 * Gets the users for a preference
	 * @param string $app
	 * @param string $key
	 * @param string $value
	 * @return array
	 * @deprecated use getUsersForUserValue of \OCP\IConfig instead
	 */
	public function getUsersForValue($app, $key, $value) {
		return $this->config->getUsersForUserValue($app, $key, $value);
	}

	/**
	 * Deletes a key
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @deprecated use deleteUserValue of \OCP\IConfig instead
	 *
	 * Deletes a key.
	 */
	public function deleteKey($user, $app, $key) {
		$this->config->deleteUserValue($user, $app, $key);
	}

	/**
	 * Remove user from preferences
	 * @param string $user user
	 * @deprecated use deleteAllUserValues of \OCP\IConfig instead
	 *
	 * Removes all keys in preferences belonging to the user.
	 */
	public function deleteUser($user) {
		$this->config->deleteAllUserValues($user);
	}

	/**
	 * Remove app from all users
	 * @param string $app app
	 * @deprecated use deleteAppFromAllUsers of \OCP\IConfig instead
	 *
	 * Removes all keys in preferences belonging to the app.
	 */
	public function deleteAppFromAllUsers($app) {
		$this->config->deleteAppFromAllUsers($app);
	}
}
