<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Config\Event;

use OCP\EventDispatcher\Event;
use OCP\Files\Config\ICachedMountInfo;

/**
 * Event emitted when a user mount was removed.
 *
 * @since 31.0.6
 */
class UserMountRemovedEvent extends Event {
	/**
	 * Creates a new @see UserMountRemovedEvent
	 *
	 * @since 31.0.6
	 */
	public function __construct(
		public readonly ICachedMountInfo $mountPoint,
	) {
		parent::__construct();
	}
}
