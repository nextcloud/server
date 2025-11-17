<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Remote;

use OCP\Remote\ICredentials;

class Credentials implements ICredentials {
	/**
	 * @param string $user
	 * @param string $password
	 */
	public function __construct(
		private $user,
		private $password,
	) {
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
