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
 * Event emitted when a user mount was moved.
 *
 * @since 32.0.0
 */
class UserMountUpdatedEvent extends Event {
	public function __construct(
		public readonly ICachedMountInfo $oldMountPoint,
		public readonly ICachedMountInfo $newMountPoint,
	) {
		parent::__construct();
	}
}
