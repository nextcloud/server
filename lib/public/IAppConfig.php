<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
namespace OCP;

/**
 * This class provides an easy way for apps to store config values in the
 * database.
 * @since 7.0.0
 */
interface IAppConfig {
	/**
	 * check if a key is set in the appconfig
	 * @param string $app
	 * @param string $key
	 * @return bool
	 * @since 7.0.0
	 */
	public function hasKey($app, $key);

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
	 * @since 7.0.0
	 */
	public function getValue($app, $key, $default = null);

	/**
	 * Deletes a key
	 * @param string $app app
	 * @param string $key key
	 * @return bool
	 * @deprecated 8.0.0 use method deleteAppValue of \OCP\IConfig
	 * @since 7.0.0
	 */
	public function deleteKey($app, $key);

	/**
	 * Get the available keys for an app
	 * @param string $app the app we are looking for
	 * @return array an array of key names
	 * @deprecated 8.0.0 use method getAppKeys of \OCP\IConfig
	 *
	 * This function gets all keys of an app. Please note that the values are
	 * not returned.
	 * @since 7.0.0
	 */
	public function getKeys($app);

	/**
	 * get multiply values, either the app or key can be used as wildcard by setting it to false
	 *
	 * @param string|false $key
	 * @param string|false $app
	 * @return array|false
	 * @since 7.0.0
	 */
	public function getValues($app, $key);

	/**
	 * sets a value in the appconfig
	 * @param string $app app
	 * @param string $key key
	 * @param string|float|int $value value
	 * @deprecated 8.0.0 use method setAppValue of \OCP\IConfig
	 *
	 * Sets a value. If the key did not exist before it will be created.
	 * @return void
	 * @since 7.0.0
	 */
	public function setValue($app, $key, $value);

	/**
	 * Get all apps using the config
	 * @return array an array of app ids
	 *
	 * This function returns a list of all apps that have at least one
	 * entry in the appconfig table.
	 * @since 7.0.0
	 */
	public function getApps();

	/**
	 * Remove app from appconfig
	 * @param string $app app
	 * @return bool
	 * @deprecated 8.0.0 use method deleteAppValue of \OCP\IConfig
	 *
	 * Removes all keys in appconfig belonging to the app.
	 * @since 7.0.0
	 */
	public function deleteApp($app);
}
