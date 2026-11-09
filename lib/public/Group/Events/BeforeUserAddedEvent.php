<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Group\Events;

use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;
use OCP\IGroup;
use OCP\IUser;

/**
 * @since 18.0.0
 */
#[Listenable(since: '18.0.0')]
class BeforeUserAddedEvent extends Event {
	/**
	 * @since 18.0.0
	 */
	public function __construct(
		private readonly IGroup $group,
		private readonly IUser $user,
	) {
		parent::__construct();
	}

	/**
	 * @since 18.0.0
	 */
	public function getGroup(): IGroup {
		return $this->group;
	}

	/**
	 * @return IUser
	 * @since 18.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}
}
