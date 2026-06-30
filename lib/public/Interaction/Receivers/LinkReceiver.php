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
final readonly class LinkReceiver implements InteractionReceiver {
	/**
	 * @since 34.0.2
	 */
	#[\Override]
	public function getID(): ?string {
		return null;
	}
}
