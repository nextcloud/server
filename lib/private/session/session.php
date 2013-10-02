<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Session;

abstract class Session implements \ArrayAccess, \OCP\ISession {
	/**
	 * $name serves as a namespace for the session keys
	 *
	 * @param string $name
	 */
	abstract public function __construct($name);

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	abstract public function set($key, $value);

	/**
	 * @param string $key
	 * @return mixed should return null if $key does not exist
	 */
	abstract public function get($key);

	/**
	 * @param string $key
	 * @return bool
	 */
	abstract public function exists($key);

	/**
	 * should not throw any errors if $key does not exist
	 *
	 * @param string $key
	 */
	abstract public function remove($key);

	/**
	 * removes all entries within the cache namespace
	 */
	abstract public function clear();

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return $this->exists($offset);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset) {
		$this->remove($offset);
	}
}
