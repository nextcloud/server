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
 * @template-extends AProblemResponse<Http::STATUS_BAD_REQUEST, array<string, mixed>>
 */
// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#name-completed-upload
class CompleteUploadResponse extends AProblemResponse {
	/**
	 * @psalm-param H $headers
	 */
	public function __construct(array $headers = []) {
		parent::__construct(
			'https://iana.org/assignments/http-problem-types#completed-upload',
			'upload is already completed',
			[],
			Http::STATUS_BAD_REQUEST,
			$headers,
		);
	}
}
