<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Validator\Constraints;

/**
 * Validates that a value is not blank (an empty string, `false`, an empty array, or `null`
 * unless `$allowNull` is set)
 *
 * ```
 * class Person {
 *     #[NotBlank]
 *     public string $name;
 * }
 * ```
 *
 * @since 36.0.0
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class NotBlank {
	/**
	 * @param string|null $message the error message, or null to use the built-in default
	 * @param bool $allowNull whether `null` is considered valid (defaults to false)
	 * @param string[]|null $groups the validation groups this constraint belongs to
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly ?string $message = null,
		public readonly bool $allowNull = false,
		public readonly ?array $groups = null,
	) {
	}
}
