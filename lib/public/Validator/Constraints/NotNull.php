<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Validator\Constraints;

/**
 * Validates that a value is not strictly equal to `null`
 *
 * ```
 * class Person {
 *     #[NotNull]
 *     public ?string $name;
 * }
 * ```
 *
 * @since 36.0.0
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class NotNull {
	/**
	 * @param string|null $message the error message, or null to use the built-in default
	 * @param string[]|null $groups the validation groups this constraint belongs to
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly ?string $message = null,
		public readonly ?array $groups = null,
	) {
	}
}
