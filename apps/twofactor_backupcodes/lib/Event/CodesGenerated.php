<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Event;

use OCP\EventDispatcher\Event;
use OCP\IUser;

class CodesGenerated extends Event {

	public function __construct(
		private IUser $user,
	) {
		parent::__construct();
	}

	/**
	 * @return IUser
	 */
	public function getUser(): IUser {
		return $this->user;
	}
}
