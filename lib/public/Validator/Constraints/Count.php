<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Validator\Constraints;

/**
 * Validates a collection's element count
 *
 * ```
 * class Person {
 *     #[Count(min: 1, max: 5)]
 *     public array $nicknames; // an array of strings
 * }
 * ```
 *
 * @since 36.0.0
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class Count {
	/**
	 * @param int<0, max>|null $min the minimum expected number of elements
	 * @param int<0, max>|null $max the maximum expected number of elements
	 * @param int<0, max>|null $exactly the exact expected number of elements, equivalent to setting `$min` and `$max` to the same value
	 * @param string|null $minMessage the error message when there are too few elements, or null to use the built-in default
	 * @param string|null $maxMessage the error message when there are too many elements, or null to use the built-in default
	 * @param string|null $exactMessage the error message when `$exactly` is set and the count differs, or null to use the built-in default
	 * @param string[]|null $groups the validation groups this constraint belongs to
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly ?int $min = null,
		public readonly ?int $max = null,
		public readonly ?int $exactly = null,
		public readonly ?string $minMessage = null,
		public readonly ?string $maxMessage = null,
		public readonly ?string $exactMessage = null,
		public readonly ?array $groups = null,
	) {
		if ($min === null && $max === null && $exactly === null) {
			throw new \InvalidArgumentException('At least one of "min", "max" or "exactly" must be set on ' . self::class . '.');
		}
	}
}
