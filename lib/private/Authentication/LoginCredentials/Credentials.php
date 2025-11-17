<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\LoginCredentials;

use OCP\Authentication\LoginCredentials\ICredentials;

class Credentials implements ICredentials {
	/**
	 * @param string $uid
	 * @param string $loginName
	 * @param string $password
	 */
	public function __construct(
		private $uid,
		private $loginName,
		private $password,
	) {
	}

	/**
	 * @return string
	 */
	public function getUID() {
		return $this->uid;
	}

	/**
	 * @return string
	 */
	public function getLoginName() {
		return $this->loginName;
	}

	/**
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}
}
