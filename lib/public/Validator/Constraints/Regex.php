<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Validator\Constraints;

/**
 * Validates that a value matches (or does not match) a regular expression
 *
 * ```
 * class Person {
 *     #[Regex('/^[a-z0-9_]+$/i')]
 *     public string $username;
 * }
 * ```
 *
 * @since 36.0.0
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class Regex {
	/**
	 * @param string $pattern the regular expression to match, including delimiters, e.g. `/^[a-z]+$/`
	 * @param bool $match whether the value must match (true, the default) or must not match (false) the pattern
	 * @param string|null $message the error message, or null to use the built-in default
	 * @param string[]|null $groups the validation groups this constraint belongs to
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly string $pattern,
		public readonly bool $match = true,
		public readonly ?string $message = null,
		public readonly ?array $groups = null,
	) {
		if ($pattern === '') {
			throw new \InvalidArgumentException('The pattern given to ' . self::class . ' must be a non-empty string.');
		}
	}
}
