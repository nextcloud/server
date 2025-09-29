<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\LoginCredentials;

use OCP\Authentication\LoginCredentials\ICredentials;

class Credentials implements ICredentials {
	/** @var string */
	private $uid;

	/** @var string */
	private $loginName;

	/** @var string */
	private $password;

	/**
	 * @param string $uid
	 * @param string $loginName
	 * @param string $password
	 */
	public function __construct($uid, $loginName, $password) {
		$this->uid = $uid;
		$this->loginName = $loginName;
		$this->password = $password;
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
