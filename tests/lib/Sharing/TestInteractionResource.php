<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use OCP\Interaction\InteractionResource;

final readonly class TestInteractionResource implements InteractionResource {
	public function __construct(
		private string $source,
	) {
	}

	#[\Override]
	public function getID(): string {
		return $this->source;
	}
}
