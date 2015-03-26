<?php
/**
 * @author cetra3 <peter@parashift.com.au>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
		set_error_handler(array($this, 'trapError'));
		session_start();
		restore_error_handler();
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

	public function trapError($errorNumber, $errorString) {
		throw new \ErrorException($errorString);
	}

	private function validateSession() {
		if ($this->sessionClosed) {
			throw new \Exception('Session has been closed - no further changes to the session as allowed');
		}
	}
}
