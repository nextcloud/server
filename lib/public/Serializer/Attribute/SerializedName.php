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
 * Overrides the name of a property or method as used in the serialized output
 *
 * ```
 * class Person {
 *     #[SerializedName('full_name')]
 *     public string $name;
 * }
 * ```
 *
 * @since 36.0.0
 */
#[Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
#[Consumable(since: '36.0.0')]
final class SerializedName {
	/**
	 * @param string $serializedName the name of the property as it will be serialized
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly string $serializedName,
	) {
		if ($serializedName === '') {
			throw new \InvalidArgumentException('The name given to ' . self::class . ' must be a non-empty string.');
		}
	}
}
