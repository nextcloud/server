<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCP;

/**
 * This class provides an easy way for apps to store config values in the
 * database.
 */
interface IAppConfig {
	/**
	 * check if a key is set in the appconfig
	 * @param string $app
	 * @param string $key
	 * @return bool
	 */
	public function hasKey($app, $key);

	/**
	 * Gets the config value
	 * @param string $app app
	 * @param string $key key
	 * @param string $default = null, default value if the key does not exist
	 * @return string the value or $default
	 * @deprecated use method getAppValue of \OCP\IConfig
	 *
	 * This function gets a value from the appconfig table. If the key does
	 * not exist the default value will be returned
	 */
	public function getValue($app, $key, $default = null);

	/**
	 * Deletes a key
	 * @param string $app app
	 * @param string $key key
	 * @return bool
	 * @deprecated use method deleteAppValue of \OCP\IConfig
	 */
	public function deleteKey($app, $key);

	/**
	 * Get the available keys for an app
	 * @param string $app the app we are looking for
	 * @return array an array of key names
	 * @deprecated use method getAppKeys of \OCP\IConfig
	 *
	 * This function gets all keys of an app. Please note that the values are
	 * not returned.
	 */
	public function getKeys($app);

	/**
	 * get multiply values, either the app or key can be used as wildcard by setting it to false
	 *
	 * @param string|false $key
	 * @param string|false $app
	 * @return array
	 */
	public function getValues($app, $key);

	/**
	 * sets a value in the appconfig
	 * @param string $app app
	 * @param string $key key
	 * @param string $value value
	 * @deprecated use method setAppValue of \OCP\IConfig
	 *
	 * Sets a value. If the key did not exist before it will be created.
	 * @return void
	 */
	public function setValue($app, $key, $value);

	/**
	 * Get all apps using the config
	 * @return array an array of app ids
	 *
	 * This function returns a list of all apps that have at least one
	 * entry in the appconfig table.
	 */
	public function getApps();

	/**
	 * Remove app from appconfig
	 * @param string $app app
	 * @return bool
	 * @deprecated use method deleteAppValue of \OCP\IConfig
	 *
	 * Removes all keys in appconfig belonging to the app.
	 */
	public function deleteApp($app);
}
