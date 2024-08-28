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

	/** @var IUser */
	private $user;

	public function __construct(IUser $user) {
		parent::__construct();
		$this->user = $user;
	}

	/**
	 * @return IUser
	 */
	public function getUser(): IUser {
		return $this->user;
	}
}
