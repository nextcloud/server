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
 * Marks a property, method or class as belonging to one or more serialization groups
 *
 * Only properties whose group is part of the `groups` key of the context passed to
 * {@see \OCP\Serializer\ISerializer::serialize()} or
 * {@see \OCP\Serializer\ISerializer::deserialize()} are included.
 *
 * ```
 * class Person {
 *     #[Groups(['basic', 'detailed'])]
 *     public string $name;
 *
 *     #[Groups('detailed')]
 *     public string $address;
 * }
 * ```
 *
 * @since 36.0.0
 */
#[Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS)]
#[Consumable(since: '36.0.0')]
final class Groups {
	/**
	 * @var string[]
	 * @since 36.0.0
	 */
	public readonly array $groups;

	/**
	 * @param string|string[] $groups the groups to define on the attribute target
	 * @since 36.0.0
	 */
	public function __construct(string|array $groups) {
		$this->groups = (array)$groups;

		if (!$this->groups) {
			throw new \InvalidArgumentException('The groups given to ' . self::class . ' cannot be empty.');
		}

		foreach ($this->groups as $group) {
			if (!is_string($group) || $group === '') {
				throw new \InvalidArgumentException('The groups given to ' . self::class . ' must be a string or an array of non-empty strings.');
			}
		}
	}
}
