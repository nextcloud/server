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

/**
 * This class provides an easy way for storing user preferences.
 */
OC_Preferences::$object = new \OC\Preferences(OC_DB::getConnection());
class OC_Preferences{
	public static $object;
	/**
	 * @brief Get all users using the preferences
	 * @return array with user ids
	 *
	 * This function returns a list of all users that have at least one entry
	 * in the preferences table.
	 */
	public static function getUsers() {
		return self::$object->getUsers();
	}

	/**
	 * @brief Get all apps of a user
	 * @param string $user user
	 * @return array with app ids
	 *
	 * This function returns a list of all apps of the user that have at least
	 * one entry in the preferences table.
	 */
	public static function getApps( $user ) {
		return self::$object->getApps( $user );
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
	public static function getKeys( $user, $app ) {
		return self::$object->getKeys( $user, $app );
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
	public static function getValue( $user, $app, $key, $default = null ) {
		return self::$object->getValue( $user, $app, $key, $default );
	}

	/**
	 * @brief sets a value in the preferences
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @param string $value value
	 * @return bool
	 *
	 * Adds a value to the preferences. If the key did not exist before, it
	 * will be added automagically.
	 */
	public static function setValue( $user, $app, $key, $value ) {
		self::$object->setValue( $user, $app, $key, $value );
		return true;
	}

	/**
	 * @brief Deletes a key
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 *
	 * Deletes a key.
	 */
	public static function deleteKey( $user, $app, $key ) {
		self::$object->deleteKey( $user, $app, $key );
		return true;
	}

	/**
	 * @brief Remove app of user from preferences
	 * @param string $user user
	 * @param string $app app
	 * @return bool
	 *
	 * Removes all keys in preferences belonging to the app and the user.
	 */
	public static function deleteApp( $user, $app ) {
		self::$object->deleteApp( $user, $app );
		return true;
	}

	/**
	 * @brief Remove user from preferences
	 * @param string $user user
	 * @return bool
	 *
	 * Removes all keys in preferences belonging to the user.
	 */
	public static function deleteUser( $user ) {
		self::$object->deleteUser( $user );
		return true;
	}

	/**
	 * @brief Remove app from all users
	 * @param string $app app
	 * @return bool
	 *
	 * Removes all keys in preferences belonging to the app.
	 */
	public static function deleteAppFromAllUsers( $app ) {
		self::$object->deleteAppFromAllUsers( $app );
		return true;
	}
}
