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
 * This class provides an easy way for apps to store config values in the
 * database.
 *
 * @deprecated use \OC::$server->getAppConfig() to get an \OCP\IAppConfig instance
 */
class OC_Appconfig {
	/**
	 * @return \OCP\IAppConfig
	 */
	private static function getAppConfig() {
		return \OC::$server->getAppConfig();
	}

	/**
	 * Get all apps using the config
	 * @return array an array of app ids
	 *
	 * This function returns a list of all apps that have at least one
	 * entry in the appconfig table.
	 */
	public static function getApps() {
		return self::getAppConfig()->getApps();
	}

	/**
	 * Get the available keys for an app
	 * @param string $app the app we are looking for
	 * @return array an array of key names
	 *
	 * This function gets all keys of an app. Please note that the values are
	 * not returned.
	 */
	public static function getKeys($app) {
		return self::getAppConfig()->getKeys($app);
	}

	/**
	 * Gets the config value
	 * @param string $app app
	 * @param string $key key
	 * @param string $default = null, default value if the key does not exist
	 * @return string the value or $default
	 *
	 * This function gets a value from the appconfig table. If the key does
	 * not exist the default value will be returned
	 */
	public static function getValue($app, $key, $default = null) {
		return self::getAppConfig()->getValue($app, $key, $default);
	}

	/**
	 * check if a key is set in the appconfig
	 * @param string $app
	 * @param string $key
	 * @return bool
	 */
	public static function hasKey($app, $key) {
		return self::getAppConfig()->hasKey($app, $key);
	}

	/**
	 * sets a value in the appconfig
	 * @param string $app app
	 * @param string $key key
	 * @param string $value value
	 *
	 * Sets a value. If the key did not exist before it will be created.
	 */
	public static function setValue($app, $key, $value) {
		self::getAppConfig()->setValue($app, $key, $value);
	}

	/**
	 * Deletes a key
	 * @param string $app app
	 * @param string $key key
	 *
	 * Deletes a key.
	 */
	public static function deleteKey($app, $key) {
		self::getAppConfig()->deleteKey($app, $key);
	}

	/**
	 * Remove app from appconfig
	 * @param string $app app
	 *
	 * Removes all keys in appconfig belonging to the app.
	 */
	public static function deleteApp($app) {
		self::getAppConfig()->deleteApp($app);
	}

	/**
	 * get multiply values, either the app or key can be used as wildcard by setting it to false
	 *
	 * @param string|false $app
	 * @param string|false $key
	 * @return array
	 */
	public static function getValues($app, $key) {
		return self::getAppConfig()->getValues($app, $key);
	}
}
