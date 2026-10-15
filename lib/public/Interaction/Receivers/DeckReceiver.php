<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Interaction\Receivers;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Interaction\InteractionReceiver;

/**
 * @since 34.0.2
 */
#[Consumable(since: '34.0.2')]
final readonly class DeckReceiver implements InteractionReceiver {
	/**
	 * @since 34.0.2
	 */
	public function __construct(
		public int $cardId,
	) {
	}

	/**
	 * @since 34.0.2
	 */
	#[\Override]
	public function getID(): string {
		return (string)$this->cardId;
	}
}
