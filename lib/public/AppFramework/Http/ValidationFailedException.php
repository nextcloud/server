<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http;

use OCP\Validator\Violation;

/**
 * Thrown when a {@see \OCP\AppFramework\Http\Attribute\RequestPayload} parameter was built from
 * the request body but failed validation
 *
 * @since 36.0.0
 */
class ValidationFailedException extends \RuntimeException {
	/**
	 * @param Violation[] $violations
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly string $parameterName,
		public readonly array $violations,
	) {
		parent::__construct(sprintf('Parameter %s failed validation with %d violation(s)', $parameterName, count($violations)));
	}
}
