<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * Event triggered before the file system is setup
 *
 * @since 31.0.0
 */
class BeforeFileSystemSetupEvent extends Event {
	/**
	 * @since 31.0.0
	 */
	public function __construct(
		private IUser $user,
	) {
		parent::__construct();
	}

	/**
	 * @since 31.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}
}
