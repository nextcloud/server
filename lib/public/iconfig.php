<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 * 
 */

namespace OCP;

/**
 * Access to all the configuration options ownCloud offers
 */
interface IConfig {
	/**
	 * Sets a new system wide value
	 * @param string $key the key of the value, under which will be saved
	 * @param string $value the value that should be stored
	 * @todo need a use case for this
	 */
// 	public function setSystemValue($key, $value);

	/**
	 * Looks up a system wide defined value
	 * @param string $key the key of the value, under which it was saved
	 * @return string the saved value
	 */
	public function getSystemValue($key);


	/**
	 * Writes a new app wide value
	 * @param string $appName the appName that we want to store the value under
	 * @param string $key the key of the value, under which will be saved
	 * @param string $value the value that should be stored
	 */
	public function setAppValue($appName, $key, $value);

	/**
	 * Looks up an app wide defined value
	 * @param string $appName the appName that we stored the value under
	 * @param string $key the key of the value, under which it was saved
	 * @return string the saved value
	 */
	public function getAppValue($appName, $key);


	/**
	 * Set a user defined value
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $appName the appName that we want to store the value under
	 * @param string $key the key under which the value is being stored
	 * @param string $value the value that you want to store
	 */
	public function setUserValue($userId, $appName, $key, $value);

	/**
	 * Shortcut for getting a user defined value
	 * @param string $userId the userId of the user that we want to store the value under
	 * @param string $appName the appName that we stored the value under
	 * @param string $key the key under which the value is being stored
	 */
	public function getUserValue($userId, $appName, $key);
}
