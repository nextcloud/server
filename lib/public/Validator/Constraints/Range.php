<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Validator\Constraints;

/**
 * Validates that a number (or a datetime string) is between some minimum and maximum
 *
 * ```
 * class Person {
 *     #[Range(min: 0, max: 150)]
 *     public int $age;
 * }
 * ```
 *
 * @since 36.0.0
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class Range {
	/**
	 * @param int|float|non-empty-string|null $min the minimum value, either numeric or a datetime string representation
	 * @param int|float|non-empty-string|null $max the maximum value, either numeric or a datetime string representation
	 * @param string|null $notInRangeMessage the error message when both `$min` and `$max` are set and the value is outside the range, or null to use the built-in default
	 * @param string|null $minMessage the error message when only `$min` is set and the value is too low, or null to use the built-in default
	 * @param string|null $maxMessage the error message when only `$max` is set and the value is too high, or null to use the built-in default
	 * @param string[]|null $groups the validation groups this constraint belongs to
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly int|float|string|null $min = null,
		public readonly int|float|string|null $max = null,
		public readonly ?string $notInRangeMessage = null,
		public readonly ?string $minMessage = null,
		public readonly ?string $maxMessage = null,
		public readonly ?array $groups = null,
	) {
		if ($min === null && $max === null) {
			throw new \InvalidArgumentException('At least one of "min" or "max" must be set on ' . self::class . '.');
		}
	}
}
