<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Migration;

use OCP\IUser;
use OCP\IUserSession;

class DummyUserSession implements IUserSession {

	private ?IUser $user = null;

	public function login($uid, $password) {
	}

	public function logout() {
	}

	public function setUser($user) {
		$this->user = $user;
	}

	public function setVolatileActiveUser(?IUser $user): void {
		$this->user = $user;
	}

	public function getUser() {
		return $this->user;
	}

	public function isLoggedIn() {
		return !is_null($this->user);
	}

	/**
	 * get getImpersonatingUserID
	 *
	 * @return string|null
	 * @since 17.0.0
	 */
	public function getImpersonatingUserID() : ?string {
		return null;
	}

	/**
	 * set setImpersonatingUserID
	 *
	 * @since 17.0.0
	 */
	public function setImpersonatingUserID(bool $useCurrentUser = true): void {
		//no OP
	}
}
