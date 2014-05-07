<?php
/**
 * ownCloud
 *
 * @author Thomas Tanghus
 * @author Robin Appelman
 * @copyright 2013 Thomas Tanghus thomas@tanghus.net
 * @copyright 2013 Robin Appelman icewind@owncloud.com
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
 * Session interface
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * Interface ISession
 *
 * wrap PHP's internal session handling into the ISession interface
 */
interface ISession {

	/**
	 * Set a value in the session
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value);

	/**
	 * Get a value from the session
	 *
	 * @param string $key
	 * @return mixed should return null if $key does not exist
	 */
	public function get($key);

	/**
	 * Check if a named key exists in the session
	 *
	 * @param string $key
	 * @return bool
	 */
	public function exists($key);

	/**
	 * Remove a $key/$value pair from the session
	 *
	 * @param string $key
	 */
	public function remove($key);

	/**
	 * Reset and recreate the session
	 */
	public function clear();

	/**
	 * Close the session and release the lock
	 */
	public function close();

}
