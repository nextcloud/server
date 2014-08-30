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
 * wrap php's internal session handling into the Session interface
 *
 * @package OC\Session
 */
class Internal extends Session {
	public function __construct($name) {
		session_name($name);
		session_start();
		if (!isset($_SESSION)) {
			throw new \Exception('Failed to start session');
		}
	}

	public function __destruct() {
		$this->close();
	}

	/**
	 * @param string $key
	 * @param integer $value
	 */
	public function set($key, $value) {
		$this->validateSession();
		$_SESSION[$key] = $value;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		if (!$this->exists($key)) {
			return null;
		}
		return $_SESSION[$key];
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists($key) {
		return isset($_SESSION[$key]);
	}

	/**
	 * @param string $key
	 */
	public function remove($key) {
		if (isset($_SESSION[$key])) {
			unset($_SESSION[$key]);
		}
	}


	public function clear() {
		session_unset();
		@session_regenerate_id(true);
		@session_start();
		$_SESSION = array();
	}

	public function close() {
		session_write_close();
		parent::close();
	}

    public function reopen() {
        throw new \Exception('The session cannot be reopened - reopen() is ony to be used in unit testing.');
    }

    private function validateSession() {
		if ($this->sessionClosed) {
			throw new \Exception('Session has been closed - no further changes to the session as allowed');
		}
	}
}
