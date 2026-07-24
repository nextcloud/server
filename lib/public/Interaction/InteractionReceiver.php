<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Interaction;

use OCP\AppFramework\Attribute\Implementable;

/**
 * @since 34.0.2
 */
#[Implementable(since: '34.0.2')]
interface InteractionReceiver {
	/**
	 * Returns the ID that uniquely identifies this receiver.
	 *
	 * @since 34.0.2
	 */
	public function getID(): ?string;
}
