<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\Events;

use OCP\AppFramework\Attribute\Consumable;
use OCP\EventDispatcher\Event;
use OCP\Share\IShare;

/**
 * @since 35.0.0
 */
// TODO: Create separate events and listen for each
#[Consumable(since: '35.0.0')]
final class AfterShareModifiedEvent extends Event {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		public readonly IShare $share,
	) {
		parent::__construct();
	}

	/**
	 * @since 35.0.0
	 */
	public function getShare(): IShare {
		return $this->share;
	}
}
