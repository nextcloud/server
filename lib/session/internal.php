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
class Internal extends Memory {
	public function __construct($name) {
		session_name($name);
		session_start();
		if (!isset($_SESSION)) {
			throw new \Exception('Failed to start session');
		}
		$this->data = $_SESSION;
	}

	public function __destruct() {
		$_SESSION = $this->data;
		session_write_close();
	}

	public function clear() {
		session_unset();
		@session_regenerate_id(true);
		@session_start();
		$this->data = $_SESSION = array();
	}
}
