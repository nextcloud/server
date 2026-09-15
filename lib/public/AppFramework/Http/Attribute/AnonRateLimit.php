<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * Attribute for controller methods that want to limit the times a not logged-in
 * guest can call the endpoint in a given time period.
 *
 * @since 27.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class AnonRateLimit extends ARateLimit {
	/**
	 * @param int $limit The maximum number of requests that can be made in the given period in seconds.
	 * @param int $period The time period in seconds.
	 * @param list<class-string<\Throwable>> $exceptions Exception types that count the request
	 *        towards the rate limit when thrown by the controller method. When non-empty,
	 *        requests are not counted upfront anymore but only when they fail with one of
	 *        the listed exceptions. An already reached limit is still enforced upfront and
	 *        rejects further requests without invoking the controller.
	 * @since 36.0.0
	 */
	public function __construct(
		int $limit,
		int $period,
		protected array $exceptions = [],
	) {
		parent::__construct($limit, $period);
	}

	/**
	 * Exception types that count a request towards the rate limit when thrown
	 *
	 * @return list<class-string<\Throwable>>
	 * @since 36.0.0
	 */
	public function getExceptions(): array {
		return $this->exceptions;
	}
}
