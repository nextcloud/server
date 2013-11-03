<?php
/**
 * ownCloud
 *
 * @author Bart Visscher
 * @copyright 2013 Bart Visscher bartv@thisnet.nl
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
 * Public interface of ownCloud for apps to use.
 * Config interface
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
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
