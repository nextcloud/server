<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\Mount\IMountPoint;
use OCP\IUser;

/**
 * Event triggered after the users home mount has been setup, before any other
 * mounts are setup.
 *
 * @since 34.0.0
 */
class UserHomeSetupEvent extends Event {
	/**
	 * @since 34.0.0
	 */
	public function __construct(
		private readonly IUser $user,
		private readonly IMountPoint $homeMount,
	) {
		parent::__construct();
	}

	/**
	 * @since 34.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @since 34.0.0
	 */
	public function getHomeMount(): IMountPoint {
		return $this->homeMount;
	}
}
