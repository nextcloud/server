<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Attribute;

use Attribute;

/**
 * Abstract base attribute to declare an API's stability.
 *
 * @since 32.0.0
 */
#[Consumable(since: '32.0.0')]
abstract class ASince {
	/**
	 * @param string $since For shipped apps and server code such as core/ and lib/,
	 *                      this should be the server version. For other apps it
	 *                      should be the semantic app version.
	 */
	public function __construct(
		protected string $since,
	) {
	}

	public function getSince(): string {
		return $this->since;
	}
}
