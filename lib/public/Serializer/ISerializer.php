<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Serializer;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Serializes and deserializes PHP data structures to and from a wire format
 *
 * Which properties end up in the serialized output, and under which name, can be
 * controlled with {@see \OCP\Serializer\Attribute\Ignore},
 * {@see \OCP\Serializer\Attribute\Groups}, {@see \OCP\Serializer\Attribute\SerializedName}
 * and {@see \OCP\Serializer\Attribute\SerializedPath}.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface ISerializer {
	/**
	 * Serializes data of any kind into the given format
	 *
	 * @param mixed $data the data to serialize, e.g. an object, or an array of objects
	 * @param Format $format the output format
	 * @param array<string, mixed> $context context options, e.g. `['groups' => ['group1']]`
	 *                                      to only serialize properties tagged with one of those groups
	 * @since 36.0.0
	 */
	public function serialize(mixed $data, Format $format = Format::JSON, array $context = []): string;

	/**
	 * Deserializes data of the given format into an instance of `$type`
	 *
	 * @template T
	 * @param string $data the raw data to deserialize
	 * @param class-string<T>|non-empty-string $type the class to deserialize the data into, e.g. `Person::class` or `Person::class . '[]'`
	 * @param Format $format the input format
	 * @param array<string, mixed> $context context options, e.g. `['groups' => ['group1']]`
	 *                                      to only populate properties tagged with one of those groups
	 * @return T
	 * @since 36.0.0
	 */
	public function deserialize(string $data, string $type, Format $format = Format::JSON, array $context = []): mixed;
}
