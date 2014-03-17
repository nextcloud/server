<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Session;

/**
 * Class Internal
 *
 * store session data in an in-memory array, not persistent
 *
 * @package OC\Session
 */
class Memory extends Session {
	protected $data;

	public function __construct($name) {
		//no need to use $name since all data is already scoped to this instance
		$this->data = array();
	}

	/**
	 * @param string $key
	 * @param integer $value
	 */
	public function set($key, $value) {
		$this->validateSession();
		$this->data[$key] = $value;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		if (!$this->exists($key)) {
			return null;
		}
		return $this->data[$key];
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists($key) {
		return isset($this->data[$key]);
	}

	/**
	 * @param string $key
	 */
	public function remove($key) {
		$this->validateSession();
		unset($this->data[$key]);
	}

	public function clear() {
		$this->data = array();
	}

	/**
	 * Helper function for PHPUnit execution - don't use in non-test code
	 */
	public function reopen() {
		$this->sessionClosed = false;
	}

	/**
	 * In case the session has already been locked an exception will be thrown
	 *
	 * @throws \Exception
	 */
	private function validateSession() {
		if ($this->sessionClosed) {
			throw new \Exception('Session has been closed - no further changes to the session as allowed');
		}
	}
}
