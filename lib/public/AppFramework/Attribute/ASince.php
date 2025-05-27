<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Attribute;

use Attribute;

/**
 * Attribute to declare that the API stability is limited to "implementing" the
 * class, interface, enum, etc.
 *
 * @since 32.0.0
 */
#[Consumable(since: '32.0.0')]
abstract class ASince {
	public function __construct(
		protected string $since,
	) {
	}

	public function getSince(): string {
		return $this->since;
	}
}
