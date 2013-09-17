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

interface ISession {
	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value);

	/**
	 * @param string $key
	 * @return mixed should return null if $key does not exist
	 */
	public function get($key);

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists($key);

	/**
	 * should not throw any errors if $key does not exist
	 *
	 * @param string $key
	 */
	public function remove($key);

	/**
	 * removes all entries within the cache namespace
	 */
	public function clear();

}
