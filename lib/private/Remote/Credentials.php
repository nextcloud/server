<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Remote;

use OCP\Remote\ICredentials;

class Credentials implements ICredentials {
	/** @var string */
	private $user;
	/** @var string */
	private $password;

	/**
	 * @param string $user
	 * @param string $password
	 */
	public function __construct($user, $password) {
		$this->user = $user;
		$this->password = $password;
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->user;
	}

	/**
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}
}
