<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Validator\Constraints;

/**
 * Validates that a value is one of a given set of choices
 *
 * ```
 * class Person {
 *     #[Choice(choices: ['admin', 'member', 'guest'])]
 *     public string $role;
 * }
 * ```
 *
 * @since 36.0.0
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class Choice {
	/**
	 * @param list<mixed> $choices the valid choices, must not be empty
	 * @param bool $multiple whether the value is an array of valid choices instead of a single one (defaults to false)
	 * @param int<0, max>|null $min the minimum number of valid choices, only used when `$multiple` is true
	 * @param positive-int|null $max the maximum number of valid choices, only used when `$multiple` is true
	 * @param string|null $message the error message for an invalid single choice, or null to use the built-in default
	 * @param string|null $multipleMessage the error message for an invalid choice in a multiple selection, or null to use the built-in default
	 * @param string|null $minMessage the error message when fewer than `$min` choices are given, or null to use the built-in default
	 * @param string|null $maxMessage the error message when more than `$max` choices are given, or null to use the built-in default
	 * @param string[]|null $groups the validation groups this constraint belongs to
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly array $choices,
		public readonly bool $multiple = false,
		public readonly ?int $min = null,
		public readonly ?int $max = null,
		public readonly ?string $message = null,
		public readonly ?string $multipleMessage = null,
		public readonly ?string $minMessage = null,
		public readonly ?string $maxMessage = null,
		public readonly ?array $groups = null,
	) {
		if (!$choices) {
			throw new \InvalidArgumentException('The choices given to ' . self::class . ' cannot be empty.');
		}
	}
}
