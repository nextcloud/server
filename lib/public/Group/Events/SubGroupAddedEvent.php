<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Group\Events;

use OCP\EventDispatcher\Event;
use OCP\IGroup;

/**
 * Dispatched after $child has been added as a direct subgroup of $parent.
 *
 * Listeners typically also see one {@see UserAddedEvent} per user who
 * transitively became an effective member of $parent, subject to the
 * synthesis cap configured on the group manager.
 *
 * @since 34.0.0
 */
class SubGroupAddedEvent extends Event {
	public function __construct(
		private IGroup $parent,
		private IGroup $child,
	) {
		parent::__construct();
	}

	/**
	 * @since 34.0.0
	 */
	public function getParent(): IGroup {
		return $this->parent;
	}

	/**
	 * @since 34.0.0
	 */
	public function getChild(): IGroup {
		return $this->child;
	}
}
