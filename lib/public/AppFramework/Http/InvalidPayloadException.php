<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http;

/**
 * Thrown when a {@see \OCP\AppFramework\Http\Attribute\RequestPayload} parameter could not be
 * built from the request body, e.g. because it is not valid JSON or is missing required fields
 *
 * @since 36.0.0
 */
class InvalidPayloadException extends \InvalidArgumentException {
	/**
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly string $parameterName,
		string $reason,
	) {
		parent::__construct(sprintf('Parameter %s could not be built from the request body: %s', $parameterName, $reason));
	}
}
