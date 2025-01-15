<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Response;

use OCP\AppFramework\Http;

/**
 * @template H of array<string, mixed>
 * @template-extends AProblemResponse<Http::STATUS_CONFLICT, array<string, mixed>>
 */
// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#name-mismatching-offset
class MismatchingOffsetResponse extends AProblemResponse {
	/**
	 * @psalm-param non-negative-int $expectedOffset
	 * @psalm-param non-negative-int $providedOffset
	 * @psalm-param H $headers
	 */
	public function __construct(
		int $expectedOffset,
		int $providedOffset,
		array $headers = [],
	) {
		parent::__construct(
			'https://iana.org/assignments/http-problem-types#mismatching-upload-offset',
			'offset from request does not match offset of resource',
			[
				'expected-offset' => $expectedOffset,
				'provided-offset' => $providedOffset,
			],
			Http::STATUS_CONFLICT,
			$headers,
		);
	}
}
