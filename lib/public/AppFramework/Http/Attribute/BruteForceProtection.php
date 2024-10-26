<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * Attribute for controller methods that want to protect passwords, keys, tokens
 * or other data against brute force
 *
 * @since 27.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class BruteForceProtection {
	/**
	 * @since 27.0.0
	 */
	public function __construct(
		protected string $action,
	) {
	}

	/**
	 * @since 27.0.0
	 */
	public function getAction(): string {
		return $this->action;
	}
}
