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
	 * @param int $limit The maximum number of requests that can be made in the given period in seconds.
	 * @param int $period The time period in seconds.
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
