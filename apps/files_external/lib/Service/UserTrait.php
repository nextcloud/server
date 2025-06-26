<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Service;

use OCP\IUser;
use OCP\IUserSession;

/**
 * Trait for getting user information in a service
 */
trait UserTrait {

	/** @var IUserSession */
	protected $userSession;

	/**
	 * User override
	 *
	 * @var IUser|null
	 */
	private $user = null;

	/**
	 * @return IUser|null
	 */
	protected function getUser() {
		if ($this->user) {
			return $this->user;
		}
		return $this->userSession->getUser();
	}

	/**
	 * Override the user from the session
	 * Unset with ->resetUser() when finished!
	 *
	 * @param IUser $user
	 * @return self
	 */
	public function setUser(IUser $user) {
		$this->user = $user;
		return $this;
	}

	/**
	 * Reset the user override
	 *
	 * @return self
	 */
	public function resetUser() {
		$this->user = null;
		return $this;
	}
}
