<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

/**
 * Attribute for controller methods that want to limit the times a logged-in
 * user can call the endpoint in a given time period.
 *
 * @since 27.0.0
 */
abstract class ARateLimit {
	/**
	 * @since 27.0.0
	 */
	public function __construct(
		protected int $limit,
		protected int $period,
	) {
	}

	/**
	 * @since 27.0.0
	 */
	public function getLimit(): int {
		return $this->limit;
	}

	/**
	 * @since 27.0.0
	 */
	public function getPeriod(): int {
		return $this->period;
	}
}
