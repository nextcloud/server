<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\Events;

use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;
use OCP\Share\IShare;

/**
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
#[Listenable(since: '35.0.0')]
final class ShareUpdatedEvent extends Event {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		public readonly IShare $share,
	) {
	}

	/**
	 * @since 35.0.0
	 */
	public function getShare(): IShare {
		return $this->share;
	}
}
