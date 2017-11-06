<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author cetra3 <peter@parashift.com.au>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
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

use OCP\Session\Exceptions\SessionNotAvailableException;

/**
 * Class Internal
 *
 * wrap php's internal session handling into the Session interface
 *
 * @package OC\Session
 */
class Internal extends Session {
	/**
	 * @param string $name
	 * @throws \Exception
	 */
	public function __construct($name) {
		set_error_handler(array($this, 'trapError'));
		$this->invoke('session_name', [$name]);
		try {
			$this->invoke('session_start');
		} catch (\Exception $e) {
			setcookie($this->invoke('session_name'), null, -1, \OC::$WEBROOT ?: '/');
		}
		restore_error_handler();
		if (!isset($_SESSION)) {
			throw new \Exception('Failed to start session');
		}
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
		$this->invoke('session_unset');
		$this->regenerateId();
		$this->invoke('session_start', [], true);
		$_SESSION = [];
	}

	public function close() {
		$this->invoke('session_write_close');
		parent::close();
	}

	/**
	 * Wrapper around session_regenerate_id
	 *
	 * @param bool $deleteOldSession Whether to delete the old associated session file or not.
	 * @return void
	 */
	public function regenerateId($deleteOldSession = true) {
		try {
			@session_regenerate_id($deleteOldSession);
		} catch (\Error $e) {
			$this->trapError($e->getCode(), $e->getMessage());
		}
	}

	/**
	 * Wrapper around session_id
	 *
	 * @return string
	 * @throws SessionNotAvailableException
	 * @since 9.1.0
	 */
	public function getId() {
		$id = $this->invoke('session_id', [], true);
		if ($id === '') {
			throw new SessionNotAvailableException();
		}
		return $id;
	}

	/**
	 * @throws \Exception
	 */
	public function reopen() {
		throw new \Exception('The session cannot be reopened - reopen() is ony to be used in unit testing.');
	}

	/**
	 * @param int $errorNumber
	 * @param string $errorString
	 * @throws \ErrorException
	 */
	public function trapError($errorNumber, $errorString) {
		throw new \ErrorException($errorString);
	}

	/**
	 * @throws \Exception
	 */
	private function validateSession() {
		if ($this->sessionClosed) {
			throw new SessionNotAvailableException('Session has been closed - no further changes to the session are allowed');
		}
	}

	/**
	 * @param string $functionName the full session_* function name
	 * @param array $parameters
	 * @param bool $silence whether to suppress warnings
	 * @throws \ErrorException via trapError
	 * @return mixed
	 */
	private function invoke($functionName, array $parameters = [], $silence = false) {
		try {
			if($silence) {
				return @call_user_func_array($functionName, $parameters);
			} else {
				return call_user_func_array($functionName, $parameters);
			}
		} catch(\Error $e) {
			$this->trapError($e->getCode(), $e->getMessage());
		}
	}
}
