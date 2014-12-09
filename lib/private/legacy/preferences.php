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
 * @deprecated use \OCP\IConfig methods instead
 */
class OC_Preferences{
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
	public static function getKeys( $user, $app ) {
		return \OC::$server->getConfig()->getUserKeys($user, $app);
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
	public static function getValue( $user, $app, $key, $default = null ) {
		return \OC::$server->getConfig()->getUserValue($user, $app, $key, $default);
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
	public static function setValue( $user, $app, $key, $value, $preCondition = null ) {
		try {
			\OC::$server->getConfig()->setUserValue($user, $app, $key, $value, $preCondition);
			return true;
		} catch(\OCP\PreConditionNotMetException $e) {
			return false;
		}
	}

	/**
	 * Deletes a key
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @return bool true
	 * @deprecated use deleteUserValue of \OCP\IConfig instead
	 *
	 * Deletes a key.
	 */
	public static function deleteKey( $user, $app, $key ) {
		\OC::$server->getConfig()->deleteUserValue($user, $app, $key);
		return true;
	}

	/**
	 * Remove user from preferences
	 * @param string $user user
	 * @return bool
	 * @deprecated use deleteUser of \OCP\IConfig instead
	 *
	 * Removes all keys in preferences belonging to the user.
	 */
	public static function deleteUser( $user ) {
		\OC::$server->getConfig()->deleteAllUserValues($user);
		return true;
	}

	/**
	 * Remove app from all users
	 * @param string $app app
	 * @return bool
	 * @deprecated use deleteAppFromAllUsers of \OCP\IConfig instead
	 *
	 * Removes all keys in preferences belonging to the app.
	 */
	public static function deleteAppFromAllUsers( $app ) {
		\OC::$server->getConfig()->deleteAppFromAllUsers($app);
		return true;
	}
}
