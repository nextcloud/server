<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Serializer\Attribute;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Moves a property or method value to a nested path in the serialized output
 *
 * ```
 * class Person {
 *     #[SerializedPath('[address][city]')]
 *     public string $city;
 * }
 * ```
 *
 * @since 36.0.0
 */
#[Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
#[Consumable(since: '36.0.0')]
final class SerializedPath {
	/**
	 * @param string $serializedPath a property-access path, e.g. `[address][city]`
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly string $serializedPath,
	) {
		if ($serializedPath === '') {
			throw new \InvalidArgumentException('The path given to ' . self::class . ' must be a non-empty string.');
		}
	}
}
