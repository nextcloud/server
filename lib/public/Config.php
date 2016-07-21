<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * Public interface of ownCloud for apps to use.
 * Config Class
 *
 */

/**
 * Use OCP namespace for all classes that are considered public.
 *
 * Classes that use this namespace are for use by apps, and not for use by internal
 * OC classes
 */
namespace OCP;

/**
 * This class provides functions to read and write configuration data.
 * configuration can be on a system, application or user level
 * @deprecated 8.0.0 use methods of \OCP\IConfig
 */
class Config {
	/**
	 * Gets a value from config.php
	 * @param string $key key
	 * @param mixed $default = null default value
	 * @return mixed the value or $default
	 * @deprecated 8.0.0 use method getSystemValue of \OCP\IConfig
	 *
	 * This function gets the value from config.php. If it does not exist,
	 * $default will be returned.
	 */
	public static function getSystemValue( $key, $default = null ) {
		return \OC::$server->getConfig()->getSystemValue( $key, $default );
	}

	/**
	 * Sets a value
	 * @param string $key key
	 * @param mixed $value value
	 * @return bool
	 * @deprecated 8.0.0 use method setSystemValue of \OCP\IConfig
	 *
	 * This function sets the value and writes the config.php. If the file can
	 * not be written, false will be returned.
	 */
	public static function setSystemValue( $key, $value ) {
		try {
			\OC::$server->getConfig()->setSystemValue( $key, $value );
		} catch (\Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Deletes a value from config.php
	 * @param string $key key
	 * @deprecated 8.0.0 use method deleteSystemValue of \OCP\IConfig
	 *
	 * This function deletes the value from config.php.
	 */
	public static function deleteSystemValue( $key ) {
		\OC::$server->getConfig()->deleteSystemValue( $key );
	}

	/**
	 * Gets the config value
	 * @param string $app app
	 * @param string $key key
	 * @param string $default = null, default value if the key does not exist
	 * @return string the value or $default
	 * @deprecated 8.0.0 use method getAppValue of \OCP\IConfig
	 *
	 * This function gets a value from the appconfig table. If the key does
	 * not exist the default value will be returned
	 */
	public static function getAppValue( $app, $key, $default = null ) {
		return \OC::$server->getConfig()->getAppValue( $app, $key, $default );
	}

	/**
	 * Sets a value in the appconfig
	 * @param string $app app
	 * @param string $key key
	 * @param string $value value
	 * @return boolean true/false
	 * @deprecated 8.0.0 use method setAppValue of \OCP\IConfig
	 *
	 * Sets a value. If the key did not exist before it will be created.
	 */
	public static function setAppValue( $app, $key, $value ) {
		try {
			\OC::$server->getConfig()->setAppValue( $app, $key, $value );
		} catch (\Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Gets the preference
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @param string $default = null, default value if the key does not exist
	 * @return string the value or $default
	 * @deprecated 8.0.0 use method getUserValue of \OCP\IConfig
	 *
	 * This function gets a value from the preferences table. If the key does
	 * not exist the default value will be returned
	 */
	public static function getUserValue( $user, $app, $key, $default = null ) {
		return \OC::$server->getConfig()->getUserValue( $user, $app, $key, $default );
	}

	/**
	 * Sets a value in the preferences
	 * @param string $user user
	 * @param string $app app
	 * @param string $key key
	 * @param string $value value
	 * @return bool
	 * @deprecated 8.0.0 use method setUserValue of \OCP\IConfig
	 *
	 * Adds a value to the preferences. If the key did not exist before, it
	 * will be added automagically.
	 */
	public static function setUserValue( $user, $app, $key, $value ) {
		try {
			\OC::$server->getConfig()->setUserValue( $user, $app, $key, $value );
		} catch (\Exception $e) {
			return false;
		}
		return true;
	}
}
