<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Sharing\Event;

use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;

/**
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
#[Listenable(since: '35.0.0')]
final class SharesUpdatedEvent extends Event {
	/**
	 * @param non-empty-list<string> $shareIds
	 * @experimental 35.0.0
	 */
	public function __construct(
		public readonly array $shareIds,
	) {
		parent::__construct();
	}
}
