<?php
/**
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * @author Thomas Tanghus
 * @author Robin Appelman
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

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

}
