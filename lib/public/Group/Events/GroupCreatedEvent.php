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

/**
 * @since 18.0.0
 */
#[Implementable(since: '21.0.0')]
class GroupCreatedEvent extends Event {
	/**
	 * @since 18.0.0
	 */
	public function __construct(
		private readonly IGroup $group,
	) {
		parent::__construct();
	}

	/**
	 * @since 18.0.0
	 */
	public function getGroup(): IGroup {
		return $this->group;
	}
}
