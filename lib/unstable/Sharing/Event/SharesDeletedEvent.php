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
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
#[Listenable(since: '36.0.0')]
final class SharesDeletedEvent extends Event {
	/**
	 * @param non-empty-list<non-empty-string> $shareIds
	 * @experimental 36.0.0
	 */
	public function __construct(
		public readonly array $shareIds,
	) {
		parent::__construct();
	}
}
