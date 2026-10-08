<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Group\Events;

use OCP\AppFramework\Attribute\Implementable;
use OCP\EventDispatcher\Event;
use OCP\IGroup;
use OCP\IUser;

/**
 * @since 18.0.0
 * @deprecated 20.0.0 - it can't be guaranteed that this event is triggered in
 * all case (e.g. for LDAP users this isn't possible) - if there is a valid use
 * case please reach out in the issue tracker at
 * https://github.com/nextcloud/server/issues
 */
#[Implementable(since: '18.0.0')]
class BeforeUserRemovedEvent extends Event {
	/**
	 * @since 18.0.0
	 * @deprecated 20.0.0
	 */
	public function __construct(
		private readonly IGroup $group,
		private readonly IUser $user,
	) {
		parent::__construct();
	}

	/**
	 * @since 18.0.0
	 * @deprecated 20.0.0
	 */
	public function getGroup(): IGroup {
		return $this->group;
	}

	/**
	 * @since 18.0.0
	 * @deprecated 20.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}
}
