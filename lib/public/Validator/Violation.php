<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Validator;

/**
 * One failed constraint for a single property, as reported by {@see IConstraintValidator::validate()}
 *
 * @since 36.0.0
 */
final class Violation {
	/**
	 * @param string $propertyPath the path to the invalid property, e.g. `address.city`
	 * @param string $message the human-readable, already-substituted validation error message
	 * @param mixed $invalidValue the value that failed validation
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly string $propertyPath,
		public readonly string $message,
		public readonly mixed $invalidValue,
	) {
	}
}
